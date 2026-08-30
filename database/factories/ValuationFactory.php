<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\Valuation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ValuationFactory extends Factory
{
    protected $model = Valuation::class;

    public function definition(): array
    {
        $listing = PropertyListing::factory()->create();

        return [
            'property_listing_id' => $listing->id,
            'requested_by' => null,
            'method' => 'automated',
            'estimated_value' => fake()->numberBetween(5000000, 200000000),
            'currency_code' => 'NGN',
            'confidence_score' => fake()->numberBetween(40, 90),
            'valued_at' => now(),
            'metadata' => [],
        ];
    }
}
