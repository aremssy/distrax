<?php

namespace App\Services;

use App\Models\DealScore;
use App\Models\PropertyListing;
use App\Models\RiskAssessment;
use Illuminate\Support\Facades\DB;

/**
 * Computes an explainable, versioned Distrax Deal Score (0–100). Every component is
 * stored separately in DealScore.breakdown so the UI can always render a breakdown,
 * never just a bare number. The weighted result is back-filled onto
 * PropertyListing.deal_score_cached so existing search/sort can order by it with no join.
 *
 * NOTE: component weights are placeholders (blueprint open decision #7) and should be
 * tuned by a data/pricing owner via the `deal_score_weights` setting.
 */
class DealScoreService
{
    /** Component keys, in the order the breakdown UI presents them. */
    public const COMPONENTS = [
        'discount_component',
        'verification_component',
        'location_component',
        'condition_component',
        'urgency_component',
        'negotiation_component',
        'comparable_position_component',
        'income_potential_component',
        'liquidity_component',
        'risk_penalty_component',
    ];

    /** Human labels for each breakdown component, used by the UI breakdown. */
    public const COMPONENTS_UI = [
        'discount_component' => 'Discount vs market',
        'verification_component' => 'Verification confidence',
        'location_component' => 'Location attractiveness',
        'condition_component' => 'Property condition',
        'urgency_component' => 'Seller urgency',
        'negotiation_component' => 'Negotiation flexibility',
        'comparable_position_component' => 'Comparable market position',
        'income_potential_component' => 'Income potential',
        'liquidity_component' => 'Liquidity / exit potential',
        'risk_penalty_component' => 'Risk & disclosure impact',
    ];

    public const LABELS_BY_SCORE = [
        [80.0, 'exceptional_opportunity'],
        [65.0, 'strong'],
        [50.0, 'good'],
        [35.0, 'fair'],
        [0.0, 'weak'],
    ];

    public function __construct(
        private DistanceService $distance,
        private ValuationService $valuations,
        private CurrencyConverter $currencyConverter,
    ) {}

    /**
     * Compute and persist a Deal Score for a listing, then back-fill the cached column.
     * Optionally accepts already-computed supporting models to avoid re-querying.
     * When $targetCurrency is provided, monetary comparisons are converted into that
     * currency so the score reflects the viewer's selected currency.
     */
    public function compute(PropertyListing $listing, ?string $targetCurrency = null): DealScore
    {
        return DB::transaction(function () use ($listing, $targetCurrency): DealScore {
            $targetCurrency ??= $listing->currency_code;
            $weights = $this->weights();
            $components = $this->components($listing, $targetCurrency);

            $total = 0;
            foreach ($components as $key => $value) {
                $total += $value * ($weights[$key] ?? 0);
            }
            $total = max(0, min(100, (int) round($total)));

            $score = DealScore::create([
                'property_listing_id' => $listing->id,
                'score' => $total,
                'breakdown' => [
                    'version' => 4,
                    'weights' => $weights,
                    ...$components,
                    'label' => self::labelFor($total),
                    'currency' => $targetCurrency,
                ],
                'computed_at' => now(),
            ]);

            $listing->update(['deal_score_cached' => $score->score]);

            return $score;
        });
    }

    public function labelFor(int $score): string
    {
        foreach (self::LABELS_BY_SCORE as [$threshold, $label]) {
            if ($score >= $threshold) {
                return $label;
            }
        }

        return 'weak';
    }

    /** Placeholder weights (open decision #7). Overridable from settings as a JSON map. */
    public function weights(): array
    {
        $defaults = [
            'discount_component' => 0.20,
            'verification_component' => 0.20,
            'location_component' => 0.12,
            'condition_component' => 0.08,
            'urgency_component' => 0.08,
            'negotiation_component' => 0.06,
            'comparable_position_component' => 0.10,
            'income_potential_component' => 0.06,
            'liquidity_component' => 0.05,
            'risk_penalty_component' => 0.05,
        ];

        $custom = setting('deal_score_weights', null);
        if (is_string($custom) && $custom !== '') {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                return array_merge($defaults, $decoded);
            }
        }

        return $defaults;
    }

    /**
     * All component sub-scores on a 0–100 scale. Higher is better; risk is a penalty
     * (a high risk detracts, i.e. the component approaches 0 when risk is high).
     */
    public function components(PropertyListing $listing, ?string $targetCurrency = null): array
    {
        $targetCurrency ??= $listing->currency_code;

        return [
            'discount_component' => $this->discountComponent($listing, $targetCurrency),
            'verification_component' => $this->verificationComponent($listing),
            'location_component' => (int) setting('deal_score_location_default', 60),
            'condition_component' => (int) setting('deal_score_condition_default', 60),
            'urgency_component' => $this->urgencyComponent($listing),
            'negotiation_component' => $this->negotiationComponent($listing),
            'comparable_position_component' => $this->comparablePositionComponent($listing),
            'income_potential_component' => 50,
            'liquidity_component' => 50,
            'risk_penalty_component' => $this->riskComponent($listing),
        ];
    }

    private function discountComponent(PropertyListing $listing, string $targetCurrency): int
    {
        $market = $this->valuations->latestEstimatedValue($listing);

        if (! $market || $market <= 0 || $listing->price <= 0) {
            return 50;
        }

        $valuationCurrency = $listing->valuations()->latest('valued_at')->first()?->currency_code
            ?? $listing->currency_code;

        $marketInTarget = $this->currencyConverter->convert($market, $valuationCurrency, $targetCurrency);
        $priceInTarget = $this->currencyConverter->convert($listing->price, $listing->currency_code, $targetCurrency);

        if ($marketInTarget <= 0 || $priceInTarget <= 0) {
            return 50;
        }

        $discountPct = (($marketInTarget - $priceInTarget) / $marketInTarget) * 100;

        return max(0, min(100, (int) round(50 + $discountPct * 2)));
    }

    private function verificationComponent(PropertyListing $listing): int
    {
        return match ($listing->verificationCase?->status) {
            'distrax_verified' => 100,
            'disclosure_required' => 85,
            'in_progress' => 60,
            'under_legal_review' => 30,
            'not_verified' => 10,
            default => 50,
        };
    }

    private function urgencyComponent(PropertyListing $listing): int
    {
        return match ($listing->expected_closing_period) {
            'immediate' => 90,
            '30_days' => 75,
            '60_days' => 60,
            '90_days' => 45,
            'flexible' => 30,
            default => $listing->distress_reason_category === 'urgent_cash_need' ? 80 : 50,
        };
    }

    private function negotiationComponent(PropertyListing $listing): int
    {
        return match ($listing->negotiation_flexibility) {
            'highly_negotiable' => 90,
            'make_an_offer' => 85,
            'negotiable' => 65,
            'firm' => 30,
            default => 50,
        };
    }

    private function comparablePositionComponent(PropertyListing $listing): int
    {
        $count = method_exists($listing, 'comparables') ? $listing->comparables()->count() : 0;
        if ($count === 0) {
            return 50;
        }

        $avgSimilarity = $listing->comparables()->avg('similarity_score') ?? 50;

        return max(0, min(100, (int) round($avgSimilarity)));
    }

    /** Risk is a penalty: high known risk drives this component toward zero. */
    private function riskComponent(PropertyListing $listing): int
    {
        $risks = RiskAssessment::where('property_listing_id', $listing->id)->get();

        if ($risks->isEmpty()) {
            return 100;
        }

        $penalty = $risks->sum(fn (RiskAssessment $r) => match ($r->level) {
            'high' => 20,
            'medium' => 8,
            default => 0,
        });

        return max(0, min(100, 100 - $penalty));
    }
}
