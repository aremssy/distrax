<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\RiskAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskAssessmentFactory extends Factory
{
    protected $model = RiskAssessment::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'risk_area' => fake()->randomElement(RiskAssessment::AREAS),
            'level' => fake()->randomElement(['low', 'medium', 'high']),
            'why_explanation' => fake()->sentence(),
            'evidence_ref_id' => null,
            'factors' => [],
            'notes' => null,
            'assessed_at' => now(),
        ];
    }
}
