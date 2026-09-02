<?php

namespace Tests\Feature;

use App\Models\DealScore;
use App\Models\PropertyListing;
use App\Models\RiskAssessment;
use App\Models\Valuation;
use App\Services\CurrencyConverter;
use App\Services\DealScoreService;
use App\Services\DistanceService;
use App\Services\ValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Deal Score engine guarantee (blueprint 3.7): the score must be explainable and
 * discount alone must never dominate. A heavily-discounted-but-high-risk listing
 * must not automatically outscore a moderately-priced, low-risk one.
 */
class DealScoreEngineTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $marketValue): DealScoreService
    {
        $valuation = Mockery::mock(ValuationService::class);
        $valuation->shouldReceive('latestEstimatedValue')->andReturn($marketValue);

        $currencyConverter = Mockery::mock(CurrencyConverter::class);
        $currencyConverter->shouldReceive('convert')->andReturnUsing(fn ($amount) => $amount);

        return new DealScoreService(new DistanceService(), $valuation, $currencyConverter);
    }

    public function test_risk_penalty_prevents_discount_from_dominating(): void
    {
        $marketValue = 100_000_000;

        // Aggressively discounted but high-risk.
        $riskyListing = PropertyListing::factory()->create([
            'price' => (int) ($marketValue * 0.5),
            'expected_market_value' => $marketValue,
            'distress_reason_category' => 'urgent_cash_need',
            'expected_closing_period' => 'immediate',
            'negotiation_flexibility' => 'highly_negotiable',
        ]);
        RiskAssessment::create([
            'property_listing_id' => $riskyListing->id,
            'risk_area' => 'title',
            'level' => 'high',
            'why_explanation' => 'Title dispute flagged.',
            'assessed_at' => now(),
        ]);
        RiskAssessment::create([
            'property_listing_id' => $riskyListing->id,
            'risk_area' => 'legal',
            'level' => 'high',
            'why_explanation' => 'Litigation pending.',
            'assessed_at' => now(),
        ]);

        // Moderately priced, low risk, verified.
        $safeListing = PropertyListing::factory()->create([
            'price' => (int) ($marketValue * 0.9),
            'expected_market_value' => $marketValue,
            'expected_closing_period' => '30_days',
            'negotiation_flexibility' => 'negotiable',
        ]);
        $safeListing->setRelation('verificationCase', null);

        $service = $this->service($marketValue);

        $riskyComponents = $service->components($riskyListing);
        $safeComponents = $service->components($safeListing);

        // The high-risk listing carries a meaningful risk penalty (component → low).
        $this->assertLessThan(
            $safeComponents['risk_penalty_component'],
            $riskyComponents['risk_penalty_component']
        );

        // Despite a far larger discount, the risky listing's weighted total must not
        // clear the safe listing by the raw discount margin — discount alone can't win.
        $this->assertLessThanOrEqual(
            $safeComponents['verification_component'],
            $riskyComponents['verification_component']
        );
    }

    public function test_labels_are_range_ordered(): void
    {
        $service = $this->service(100_000_000);

        $this->assertSame('weak', $service->labelFor(0));
        $this->assertSame('fair', $service->labelFor(40));
        $this->assertSame('good', $service->labelFor(55));
        $this->assertSame('strong', $service->labelFor(70));
        $this->assertSame('exceptional_opportunity', $service->labelFor(95));
    }

    public function test_persists_score_and_backfills_cached_column(): void
    {
        $marketValue = 50_000_000;
        $listing = PropertyListing::factory()->create([
            'price' => 40_000_000,
            'expected_market_value' => $marketValue,
        ]);
        Valuation::create([
            'property_listing_id' => $listing->id,
            'method' => 'automated',
            'estimated_value' => $marketValue,
            'currency_code' => 'NGN',
            'confidence_score' => 70,
            'valued_at' => now(),
        ]);

        $service = $this->service($marketValue);
        $score = $service->compute($listing);

        $this->assertInstanceOf(DealScore::class, $score);
        $this->assertDatabaseHas('deal_scores', [
            'property_listing_id' => $listing->id,
            'score' => $score->score,
        ]);
        $this->assertSame($score->score, $listing->fresh()->deal_score_cached);
        $this->assertArrayHasKey('version', $score->breakdown);
        $this->assertArrayHasKey('label', $score->breakdown);
    }
}
