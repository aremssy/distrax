<?php

namespace App\Services;

use App\Models\PropertyListing;

/**
 * Orchestrates the Intelligence pipeline for a listing: market valuation +
 * comparables, the eight-area risk snapshot, and the Deal Score — then
 * back-fills the cached score for search/sort. Used by the property page and as
 * the recompute entry point on verification finalize / price change / disclosure /
 * comparable changes.
 */
class IntelligenceService
{
    public function __construct(
        private ValuationService $valuations,
        private RiskAssessmentService $risks,
        private DealScoreService $scores,
    ) {}

    /** Fresh valuation + risk snapshot + deal score for a listing. */
    public function analyze(PropertyListing $listing, ?string $currencyCode = null): void
    {
        $this->valuations->valuate($listing);
        $this->risks->assess($listing);
        $this->scores->compute($listing, $currencyCode);
    }

    /** Recompute just the Deal Score (price/freshness/urgency changes). */
    public function recomputeScore(PropertyListing $listing, ?string $currencyCode = null): void
    {
        $this->scores->compute($listing, $currencyCode);
    }

    /** Recompute everything after a relevant change (verification, price, disclosure). */
    public function recompute(PropertyListing $listing, ?string $currencyCode = null): void
    {
        $this->analyze($listing, $currencyCode);
    }
}
