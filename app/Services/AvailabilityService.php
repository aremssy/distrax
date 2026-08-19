<?php

namespace App\Services;

use App\Models\AvailabilityCalendar;
use App\Models\HotelBooking;
use App\Models\PropertyListing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityService
{
    /**
     * Return per-date availability for a date window.
     *
     * Each entry:
     *   date          – Y-m-d
     *   is_available  – false if owner-blocked or an active booking covers the night
     *   price         – per-night price (override_price or listing base price)
     *
     * @return array{
     *     dates: list<array{date: string, is_available: bool, price: int}>,
     *     summary: array{nights: int, available_nights: int, total_if_booked: int, currency: string}
     * }
     */
    public function getWindow(PropertyListing $listing, Carbon $from, Carbon $to): array
    {
        $nights = (int) $from->diffInDays($to);

        // Calendar overrides keyed by date string
        $calendarRows = AvailabilityCalendar::where('property_listing_id', $listing->id)
            ->whereBetween('date', [$from->toDateString(), $to->copy()->subDay()->toDateString()])
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        // Active bookings that overlap this window
        $bookedNights = $this->bookedNightSet($listing->id, $from, $to);

        $currency = setting('default_currency', 'BDT');
        $basePrice = (int) $listing->price;
        $dates = [];
        $availableNights = 0;
        $totalIfBooked = 0;

        foreach (CarbonPeriod::create($from, $to->copy()->subDay()) as $date) {
            $key = $date->toDateString();
            $cal = $calendarRows->get($key);

            $isBlocked = ($cal !== null && ! $cal->is_available);
            $isBooked = isset($bookedNights[$key]);
            $available = ! $isBlocked && ! $isBooked;

            $price = ($cal?->override_price !== null) ? (int) $cal->override_price : $basePrice;

            $dates[] = [
                'date' => $key,
                'is_available' => $available,
                'price' => $price,
            ];

            if ($available) {
                $availableNights++;
                $totalIfBooked += $price;
            }
        }

        return [
            'dates' => $dates,
            'summary' => [
                'nights' => $nights,
                'available_nights' => $availableNights,
                'total_if_booked' => $totalIfBooked,
                'currency' => $currency,
            ],
        ];
    }

    /**
     * Check whether ALL nights in the range are available.
     * Throws nothing — returns false on conflict.
     */
    public function isRangeAvailable(PropertyListing $listing, Carbon $checkIn, Carbon $checkOut): bool
    {
        $lastNight = $checkOut->copy()->subDay();

        // Check calendar blocks
        $blocked = AvailabilityCalendar::where('property_listing_id', $listing->id)
            ->where('is_available', false)
            ->whereBetween('date', [$checkIn->toDateString(), $lastNight->toDateString()])
            ->exists();

        if ($blocked) {
            return false;
        }

        // Check conflicting bookings
        return ! HotelBooking::where('property_listing_id', $listing->id)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString())
            ->exists();
    }

    /**
     * Compute the total amount (sum of per-night prices) for a range.
     * Does NOT check availability — call isRangeAvailable first.
     */
    public function computeAmount(PropertyListing $listing, Carbon $checkIn, Carbon $checkOut): int
    {
        $lastNight = $checkOut->copy()->subDay();
        $basePrice = (int) $listing->price;

        $overrides = AvailabilityCalendar::where('property_listing_id', $listing->id)
            ->whereNotNull('override_price')
            ->whereBetween('date', [$checkIn->toDateString(), $lastNight->toDateString()])
            ->pluck('override_price', 'date')
            ->map(fn ($p) => (int) $p)
            ->toArray();

        $total = 0;

        foreach (CarbonPeriod::create($checkIn, $lastNight) as $night) {
            $total += $overrides[$night->toDateString()] ?? $basePrice;
        }

        return $total;
    }

    /**
     * Upsert availability_calendar rows for a date range.
     *
     * @param  array{is_available?: bool, override_price?: int|null}  $values
     */
    public function setRange(PropertyListing $listing, Carbon $from, Carbon $to, array $values): void
    {
        $rows = [];
        $now = now()->toDateTimeString();

        foreach (CarbonPeriod::create($from, $to->copy()->subDay()) as $date) {
            $row = [
                'property_listing_id' => $listing->id,
                'date' => $date->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (array_key_exists('is_available', $values)) {
                $row['is_available'] = (bool) $values['is_available'];
            }

            if (array_key_exists('override_price', $values)) {
                $row['override_price'] = $values['override_price'];
            }

            $rows[] = $row;
        }

        if (empty($rows)) {
            return;
        }

        $update = array_filter([
            'is_available' => array_key_exists('is_available', $values) ? (bool) $values['is_available'] : null,
            'override_price' => array_key_exists('override_price', $values) ? $values['override_price'] : null,
            'updated_at' => $now,
        ], fn ($v) => $v !== null || array_key_exists('override_price', $values));

        AvailabilityCalendar::upsert($rows, ['property_listing_id', 'date'], array_keys(
            array_filter(['is_available' => true, 'override_price' => true, 'updated_at' => true],
                fn ($_, $k) => array_key_exists($k === 'updated_at' ? 'updated_at' : $k, $update),
                ARRAY_FILTER_USE_BOTH
            )
        ));
    }

    /**
     * Build a set of date strings that are covered by active bookings.
     *
     * @return array<string, true>
     */
    private function bookedNightSet(int $listingId, Carbon $from, Carbon $to): array
    {
        $bookings = HotelBooking::where('property_listing_id', $listingId)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->where('check_in', '<', $to->toDateString())
            ->where('check_out', '>', $from->toDateString())
            ->get(['check_in', 'check_out']);

        $set = [];

        foreach ($bookings as $booking) {
            foreach (CarbonPeriod::create($booking->check_in, $booking->check_out->copy()->subDay()) as $night) {
                $set[$night->toDateString()] = true;
            }
        }

        return $set;
    }
}
