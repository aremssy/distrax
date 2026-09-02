<?php

namespace App\Services;

use App\Models\ComparableProperty;
use App\Models\PropertyListing;
use App\Models\Valuation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Produces an automated market valuation from comparable properties, and a
 * price-per-sqm estimate. All numbers here are estimates — the UI must label them
 * as such with a confidence level, never as fact.
 */
class ValuationService
{
    public function __construct(
        private ComparableService $comparables,
        private CurrencyConverter $currencyConverter,
    ) {}

    /** The most recent estimated market value for a listing, or null. */
    public function latestEstimatedValue(PropertyListing $listing): ?int
    {
        $valuation = Valuation::where('property_listing_id', $listing->id)
            ->where('method', 'automated')
            ->latest('valued_at')
            ->first();

        return $valuation?->estimated_value;
    }

    /**
     * Run a full valuation: find/refresh comparables, derive price-per-sqm, and
     * store a Valuation row with a confidence score.
     */
    public function valuate(PropertyListing $listing, ?int $requestedBy = null): Valuation
    {
        [$valuation, $comps] = $this->comparables->match($listing, $requestedBy);

        $pricePerSqm = $this->pricePerSqm($listing);
        $marketValue = $this->deriveMarketValue($listing, $comps);
        $confidence = $this->confidence($comps);

        $valuation->update([
            'estimated_value' => $marketValue,
            'confidence_score' => $confidence,
            'metadata' => [
                'price_per_sqm' => $pricePerSqm,
                'comparable_count' => $comps->count(),
            ],
        ]);

        return $valuation->fresh();
    }

    /** Estimated price per square metre for the listing (or null if no area). */
    public function pricePerSqm(PropertyListing $listing): ?int
    {
        if (! $listing->area_sqft || $listing->area_sqft <= 0 || $listing->price <= 0) {
            return null;
        }

        // area_sqft → square metres
        $sqm = $listing->area_sqft / 10.7639;

        return $sqm > 0 ? (int) round($listing->price / $sqm) : null;
    }

    /** Discount/premium percentage vs the latest automated valuation (null if none). */
    public function discountPct(PropertyListing $listing, ?int $marketValue = null, ?string $toCurrency = null): ?float
    {
        $marketValue ??= $this->latestEstimatedValue($listing);

        if (! $marketValue || $marketValue <= 0 || $listing->price <= 0) {
            return null;
        }

        $market = $marketValue;
        $price = (float) $listing->price;

        if ($toCurrency) {
            $from = $listing->valuations()->latest('id')->value('currency_code') ?? $listing->currency_code;
            $market = $this->currencyConverter->convert($market, $from, $toCurrency);
            $price = $this->currencyConverter->convert($price, $listing->currency_code, $toCurrency);
        }

        if ($market <= 0) {
            return null;
        }

        return round((($market - $price) / $market) * 100, 1);
    }

    private function deriveMarketValue(PropertyListing $listing, Collection $comps): int
    {
        $prices = $comps->pluck('sale_price')->filter(fn ($p) => $p && $p > 0);

        if ($prices->isEmpty()) {
            // Fall back to the seller's declared expected market value, then asking.
            return $listing->expected_market_value ?? $listing->price;
        }

        $median = $prices->sort()->values();

        $medianValue = $median->count() % 2 === 0
            ? (int) round(($median[($median->count() / 2) - 1] + $median[$median->count() / 2]) / 2)
            : $median[intdiv($median->count(), 2)];

        return (int) $medianValue;
    }

    private function confidence(Collection $comps): int
    {
        if ($comps->isEmpty()) {
            return 10;
        }

        $avgSimilarity = (int) round($comps->avg('similarity_score') ?? 0);
        $count = $comps->count();

        return match (true) {
            $avgSimilarity >= 80 && $count >= 5 => 85,
            $avgSimilarity >= 70 && $count >= 3 => 70,
            $avgSimilarity >= 60 && $count >= 1 => 55,
            default => 35,
        };
    }
}
