<?php

namespace App\Services;

use App\Models\PropertyListing;
use App\Models\Zone;

class SavedSearchMatcherService
{
    /**
     * Descendant ids per zone, memoised for the lifetime of the instance.
     *
     * The matcher is called once per saved search when a listing is published,
     * so without this the same zone tree would be resolved thousands of times.
     *
     * @var array<int, array<int>>
     */
    private array $descendantCache = [];

    /**
     * Determine whether a listing satisfies all non-null criteria from a saved search.
     *
     * Criteria keys match the Phase-4 index filter params:
     *   type, zone_id, include_children, min_price, max_price,
     *   bedrooms, bathrooms, allowed_for, furnished, parking, advance_months.
     *
     * @param  array<string, mixed>  $criteria
     */
    public function matches(PropertyListing $listing, array $criteria): bool
    {
        if (isset($criteria['type']) && $listing->type !== $criteria['type']) {
            return false;
        }

        if (isset($criteria['zone_id'])) {
            if (! $this->zoneMatches($listing, (int) $criteria['zone_id'], (bool) ($criteria['include_children'] ?? false))) {
                return false;
            }
        }

        if (isset($criteria['min_price']) && $listing->price < (int) $criteria['min_price']) {
            return false;
        }

        if (isset($criteria['max_price']) && $listing->price > (int) $criteria['max_price']) {
            return false;
        }

        if (isset($criteria['bedrooms']) && $listing->bedrooms !== (int) $criteria['bedrooms']) {
            return false;
        }

        if (isset($criteria['bathrooms']) && $listing->bathrooms !== (int) $criteria['bathrooms']) {
            return false;
        }

        if (isset($criteria['allowed_for'])) {
            // 'both' on the listing satisfies any preference; otherwise must match exactly
            if ($listing->allowed_for !== 'both' && $listing->allowed_for !== $criteria['allowed_for']) {
                return false;
            }
        }

        if (isset($criteria['furnished']) && $listing->furnished !== (bool) $criteria['furnished']) {
            return false;
        }

        if (isset($criteria['parking']) && $listing->parking !== (bool) $criteria['parking']) {
            return false;
        }

        // saved search says "max advance_months I'm willing to pay" — skip listings that demand more
        if (isset($criteria['advance_months']) && $listing->advance_months > (int) $criteria['advance_months']) {
            return false;
        }

        // Deal radar: the property must clear the requested deal-score floor.
        if (isset($criteria['min_deal_score'])) {
            $score = $listing->deal_score_cached;
            if (! is_numeric($score) || (float) $score < (float) $criteria['min_deal_score']) {
                return false;
            }
        }

        // Deal radar: the property must be discounted at least this much vs market value.
        if (isset($criteria['min_discount_pct'])) {
            $discount = $this->discountPctFor($listing);
            if ($discount === null || $discount < (float) $criteria['min_discount_pct']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Percentage discount of current asking price vs the latest estimated value,
     * or null when no valuation is available.
     */
    public function discountPctFor(PropertyListing $listing): ?float
    {
        $latest = $listing->valuations()->latest('id')->first();

        if (! $latest || empty($latest->estimated_value)) {
            return null;
        }

        $market = (float) $latest->estimated_value;
        if ($market <= 0) {
            return null;
        }

        return round((($market - (float) $listing->price) / $market) * 100, 2);
    }

    private function zoneMatches(PropertyListing $listing, int $zoneId, bool $includeChildren): bool
    {
        if ($listing->zone_id === $zoneId) {
            return true;
        }

        if (! $includeChildren) {
            return false;
        }

        return in_array($listing->zone_id, $this->descendantIdsFor($zoneId), true);
    }

    /**
     * @return array<int>
     */
    private function descendantIdsFor(int $zoneId): array
    {
        if (! array_key_exists($zoneId, $this->descendantCache)) {
            $this->descendantCache[$zoneId] = Zone::find($zoneId)?->descendantIds() ?? [];
        }

        return $this->descendantCache[$zoneId];
    }
}
