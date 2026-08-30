<?php

namespace Database\Factories;

use App\Models\DealScore;
use App\Models\PropertyListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealScoreFactory extends Factory
{
    protected $model = DealScore::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'score' => fake()->numberBetween(20, 95),
            'breakdown' => [
                'version' => 3,
                'discount_component' => 50,
                'verification_component' => 60,
                'risk_penalty_component' => 100,
            ],
            'computed_at' => now(),
        ];
    }
}
