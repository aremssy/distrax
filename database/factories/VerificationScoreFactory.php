<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\VerificationCase;
use App\Models\VerificationScore;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationScoreFactory extends Factory
{
    protected $model = VerificationScore::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'verification_case_id' => VerificationCase::factory(),
            'reference_id' => 'DTX-VER-'.fake()->unique()->numerify('######'),
            'score' => fake()->randomFloat(2, 40, 98),
            'seller_verification_status' => 'passed',
            'title_status' => 'passed',
            'ownership_status' => 'passed',
            'survey_status' => 'passed',
            'physical_inspection_status' => 'passed',
            'legal_review_status' => 'passed',
            'planning_status' => 'passed',
            'disclosure_count' => 0,
            'verification_date' => now(),
            'expiry_review_date' => now()->addYear()->toDateString(),
            'qr_code_url' => null,
        ];
    }
}
