<?php

namespace App\Services;

use App\Models\ComparableProperty;
use App\Models\PropertyListing;
use App\Models\Valuation;
use Illuminate\Support\Collection;

/**
 * Finds comparable properties in the same submarket (via the existing
 * DistanceService / zone proximity) and scores them by size/type/condition
 * similarity to the subject listing.
 */
class ComparableService
{
    public function __construct(private DistanceService $distance) {}

    /**
     * Store comparables for a listing against a fresh automated Valuation row.
     *
     * @return array{0: Valuation, 1: Collection<int, ComparableProperty>}
     */
    public function match(PropertyListing $listing, ?int $requestedBy = null): array
    {
        $valuation = Valuation::create([
            'property_listing_id' => $listing->id,
            'requested_by' => $requestedBy,
            'method' => 'automated',
            'estimated_value' => $listing->expected_market_value ?? $listing->price,
            'currency_code' => $listing->currency_code,
            'confidence_score' => 0,
            'valued_at' => now(),
        ]);

        $candidates = $this->queryCandidates($listing);

        $comps = $candidates->map(function (PropertyListing $candidate) use ($listing): ComparableProperty {
            $distanceKm = null;

            if ($listing->lat && $listing->lng && $candidate->lat && $candidate->lng) {
                $distanceKm = round($this->distance->haversineKm(
                    (float) $listing->lat,
                    (float) $listing->lng,
                    (float) $candidate->lat,
                    (float) $candidate->lng
                ), 2);
            }

            return ComparableProperty::create([
                'valuation_id' => $valuation->id,
                'property_listing_id' => $candidate->id,
                'external_reference' => null,
                'address' => $candidate->address,
                'sale_price' => $candidate->price,
                'sale_date' => $candidate->published_at?->toDateString(),
                'distance_km' => $distanceKm,
                'similarity_score' => $this->similarity($listing, $candidate, $distanceKm),
            ]);
        })->sortByDesc('similarity_score')->values();

        return [$valuation, $comps];
    }

    /** Same submarket (zone or geo-proximity), same broad type, active, not self. */
    private function queryCandidates(PropertyListing $listing): Collection
    {
        return PropertyListing::query()
            ->where('id', '!=', $listing->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->when($listing->zone_id, fn ($q) => $q->where('zone_id', $listing->zone_id))
            ->when($listing->type, fn ($q) => $q->where('type', $listing->type))
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->limit(10)
            ->get()
            ->filter(function (PropertyListing $candidate) use ($listing): bool {
                if (is_null($listing->lat) || is_null($listing->lng)
                    || is_null($candidate->lat) || is_null($candidate->lng)) {
                    return true; // same-zone fallback
                }

                return $this->distance->haversineKm(
                    (float) $listing->lat,
                    (float) $listing->lng,
                    (float) $candidate->lat,
                    (float) $candidate->lng
                ) <= 10; // within 10 km
            })
            ->values();
    }

    /** 0–100 similarity across size, type shape, location, negotiation posture. */
    private function similarity(PropertyListing $subject, PropertyListing $candidate, ?float $distanceKm): int
    {
        $score = 0;

        if ($distanceKm !== null) {
            $score += max(0, (int) round(30 - ($distanceKm * 3)));
        } else {
            $score += 25;
        }

        if ($subject->type === $candidate->type) {
            $score += 25;
        }

        if (($subject->area_sqft ?? 0) > 0 && ($candidate->area_sqft ?? 0) > 0) {
            $ratio = min($subject->area_sqft, $candidate->area_sqft) / max($subject->area_sqft, $candidate->area_sqft);
            $score += (int) round($ratio * 25);
        } else {
            $score += 12;
        }

        if ($subject->bedrooms === $candidate->bedrooms) {
            $score += 20;
        }

        return max(0, min(100, $score));
    }
}
