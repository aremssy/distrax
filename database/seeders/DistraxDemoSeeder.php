<?php

namespace Database\Seeders;

use App\Models\ComparableProperty;
use App\Models\Deal;
use App\Models\DealScore;
use App\Models\Disclosure;
use App\Models\Inspection;
use App\Models\LegalMatter;
use App\Models\Offer;
use App\Models\PropertyListing;
use App\Models\RiskAssessment;
use App\Models\User;
use App\Models\Valuation;
use App\Models\VerificationCase;
use App\Models\VerificationScore;
use App\Models\VerificationTask;
use App\Models\Zone;
use Illuminate\Database\Seeder;

/**
 * Consolidated real-estate demo data. Creates ~20 fresh property listings spread
 * across every verification status and deal stage, with their related verification
 * cases / tasks / scores, valuations, deal scores, risk assessments, disclosures,
 * comparable sales, offers, inspections and a handful of deals at each stage.
 *
 * Deliberately additive: it never edits or removes existing rows, so it can run
 * safely on top of the existing DummyDataSeeder output.
 */
class DistraxDemoSeeder extends Seeder
{
    public function run(): void
    {
        $zones = Zone::pluck('id')->all();

        $sellers = User::factory()->count(6)->create(['seller_type' => 'individual']);
        $buyers = User::factory()->count(5)->create(['buying_for' => 'investment']);

        $record = [];

        foreach ($sellers as $seller) {
            for ($i = 0; $i < 4; $i++) {
                $listing = PropertyListing::factory()->create([
                    'owner_id' => $seller->id,
                    'zone_id' => $zones ? $zones[array_rand($zones)] : null,
                    'type' => ['sale', 'land', 'sale', 'commercial'][$i % 4],
                    'price' => $this->naira(15_000_000, 150_000_000),
                    'expected_market_value' => $this->naira(20_000_000, 180_000_000),
                    'deal_score_cached' => $this->faker()->randomFloat(2, 20, 92),
                ]);

                $record[] = $listing;
            }
        }

        foreach ($record as $index => $listing) {
            $mod = $index % 6;
            $status = match (true) {
                $mod === 0 => 'rejected',
                $mod === 1 => 'archived',
                $mod === 2 || $mod === 3 => 'pending',
                $mod === 4 => 'rented',
                default => 'active',
            };

            if ($status !== 'active') {
                $listing->update(['status' => $status, 'published_at' => null]);
            }

            $this->seedIntelligence($listing);
        }

        $active = $record->filter(fn ($l) => $l->status === 'active');
        $statuses = ['in_progress', 'in_progress', 'distrax_verified', 'distrax_verified', 'disclosure_required', 'under_legal_review', 'not_verified'];

        foreach ($active as $index => $listing) {
            $case = VerificationCase::factory()->create([
                'property_listing_id' => $listing->id,
                'status' => $statuses[$index % count($statuses)],
                'opened_at' => now()->subDays(rand(1, 20)),
                'closed_at' => $statuses[$index % count($statuses)] === 'in_progress' ? null : now()->subDay(),
                'expiry_review_date' => $statuses[$index % count($statuses)] === 'distrax_verified' ? now()->addYear() : null,
            ]);

            $listing->update(['verification_case_id' => $case->id]);

            foreach (['seller_kyc', 'document_review', 'title', 'survey', 'physical', 'ownership_authority', 'encumbrance', 'litigation', 'planning', 'final_decision'] as $task) {
                VerificationTask::factory()->create([
                    'verification_case_id' => $case->id,
                    'layer' => $task,
                    'status' => $case->status === 'in_progress' && $task !== 'seller_kyc' ? 'not_started' : 'passed',
                ]);
            }

            if ($case->status === 'distrax_verified') {
                $score = VerificationScore::factory()->create([
                    'verification_case_id' => $case->id,
                    'property_listing_id' => $listing->id,
                    'reference_id' => 'DTX-VER-'.str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT),
                    'disclosure_count' => $listing->disclosures()->count(),
                ]);

                $listing->update([
                    'deal_score_cached' => $score->score,
                    'status' => 'active',
                ]);
            }
        }

        $this->seedDeals($record, $active, $buyers);

        $available = $active->where('status', 'active')->take(4);
        foreach ($available as $listing) {
            $seller = $listing->owner;
            $buyer = $buyers->random();

            $offer = Offer::factory()->create([
                'property_listing_id' => $listing->id,
                'buyer_id' => $buyer->id,
                'amount' => $listing->price,
                'status' => 'pending',
            ]);

            Inspection::factory()->create([
                'property_listing_id' => $listing->id,
                'inspector_id' => null,
                'booked_by' => $buyer->id,
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(rand(2, 10)),
            ]);
        }
    }

    private function seedIntelligence(PropertyListing $listing): void
    {
        $valuation = Valuation::factory()->create([
            'property_listing_id' => $listing->id,
            'estimated_value' => $listing->expected_market_value ?? $this->naira(20_000_000, 180_000_000),
            'method' => 'automated',
        ]);

        DealScore::factory()->create([
            'property_listing_id' => $listing->id,
            'score' => $listing->deal_score_cached ?? $this->faker()->randomFloat(2, 20, 92),
        ]);

        RiskAssessment::factory()->count(rand(1, 3))->create([
            'property_listing_id' => $listing->id,
        ]);

        if (rand(0, 1) === 1) {
            Disclosure::factory()->create([
                'property_listing_id' => $listing->id,
                'disclosed_by' => $listing->owner_id,
            ]);
        }

        ComparableProperty::factory()->count(3)->create([
            'valuation_id' => $valuation->id,
            'property_listing_id' => $listing->id,
            'distance_km' => $this->faker()->randomFloat(2, 0.2, 10),
            'similarity_score' => $this->faker()->numberBetween(40, 95),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PropertyListing>  $record
     * @param  \Illuminate\Support\Collection<int, PropertyListing>  $active
     * @param  \Illuminate\Support\Collection<int, User>  $buyers
     */
    private function seedDeals($record, $active, $buyers): void
    {
        $dealCandidates = $active->whereIn('status', ['active', 'rented'])->take(6);

        $stages = ['offer_accepted', 'inspection', 'legal_review', 'closing', 'completed', 'completed', 'fell_through'];

        foreach ($dealCandidates as $index => $listing) {
            $stage = $stages[$index % count($stages)];
            $buyer = $buyers->random();
            $seller = $listing->owner;

            $offer = Offer::factory()->create([
                'property_listing_id' => $listing->id,
                'buyer_id' => $buyer->id,
                'amount' => $listing->price,
                'status' => $stage === 'completed' ? 'accepted' : 'countered',
            ]);

            $deal = Deal::factory()->create([
                'property_listing_id' => $listing->id,
                'offer_id' => $offer->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'agreed_amount' => $listing->price,
                'stage' => $stage === 'fell_through' ? 'fell_through' : $stage,
                'closed_at' => $stage === 'completed' ? now()->subDays(rand(1, 30)) : null,
            ]);

            if ($stage === 'completed') {
                $listing->update(['status' => 'sold']);
                $seller->increment('completed_deals_count');
                $buyer->increment('completed_deals_count');
            }

            if ($stage === 'legal_review' || $stage === 'closing' || $stage === 'completed') {
                LegalMatter::factory()->create([
                    'deal_id' => $deal->id,
                    'status' => $stage === 'completed' ? 'cleared' : 'pending',
                    'assigned_to' => User::factory()->create(['verification_status' => 'verified'])->id,
                ]);
            }
        }
    }

    private function naira(int $min, int $max): int
    {
        return $this->faker()->numberBetween($min, $max);
    }
}
