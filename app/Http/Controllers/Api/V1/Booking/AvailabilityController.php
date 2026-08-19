<?php

namespace App\Http\Controllers\Api\V1\Booking;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Booking\UpdateAvailabilityRequest;
use App\Models\PropertyListing;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends ApiController
{
    /**
     * GET /api/v1/listings/{listing}/availability?from=Y-m-d&to=Y-m-d
     *
     * Public endpoint. Returns per-date availability and pricing for hotel listings.
     * Window is capped at 365 days to prevent large result sets.
     */
    public function show(Request $request, PropertyListing $listing, AvailabilityService $service): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date', 'date_format:Y-m-d'],
            'to' => ['required', 'date', 'date_format:Y-m-d', 'after:from'],
        ]);

        if ($listing->type !== 'hotel') {
            return $this->error('Availability calendar is only available for hotel-type listings.', 422);
        }

        $from = Carbon::parse($request->input('from'))->startOfDay();
        $to = Carbon::parse($request->input('to'))->startOfDay();

        if ($from->diffInDays($to) > 365) {
            return $this->error('Date window cannot exceed 365 days.', 422);
        }

        $window = $service->getWindow($listing, $from, $to);

        return $this->success([
            'listing_id' => $listing->id,
            'base_price' => (int) $listing->price,
            'currency' => setting('default_currency', 'BDT'),
            'min_stay_nights' => (int) setting('hotel_min_stay_nights', 1),
            'dates' => $window['dates'],
            'summary' => $window['summary'],
        ]);
    }

    /**
     * PUT /api/v1/listings/{listing}/availability
     *
     * Owner-only. Block/unblock dates and set per-date price overrides.
     *
     * Body:
     *   from           Y-m-d (inclusive)
     *   to             Y-m-d (exclusive — same convention as bookings)
     *   is_available   bool  (optional)
     *   override_price int   (optional, null to clear)
     */
    public function update(UpdateAvailabilityRequest $request, PropertyListing $listing, AvailabilityService $service): JsonResponse
    {
        if ($listing->owner_id !== $request->user()->id) {
            return $this->error('You do not own this listing.', 403);
        }

        if ($listing->type !== 'hotel') {
            return $this->error('Availability calendar is only available for hotel-type listings.', 422);
        }

        $from = Carbon::parse($request->input('from'))->startOfDay();
        $to = Carbon::parse($request->input('to'))->startOfDay();

        $values = array_filter([
            'is_available' => $request->has('is_available') ? $request->boolean('is_available') : null,
            'override_price' => $request->has('override_price') ? $request->input('override_price') : null,
        ], fn ($v) => $v !== null);

        if (empty($values)) {
            return $this->error('Provide at least one of: is_available, override_price.', 422);
        }

        $service->setRange($listing, $from, $to, $values);

        // Return the updated window
        $window = $service->getWindow($listing, $from, $to);

        return $this->success([
            'updated_dates' => (int) $from->diffInDays($to),
            'dates' => $window['dates'],
        ], 'Availability updated.');
    }
}
