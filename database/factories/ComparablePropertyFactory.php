<?php

namespace Database\Factories;

use App\Models\ComparableProperty;
use App\Models\PropertyListing;
use App\Models\Valuation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComparablePropertyFactory extends Factory
{
    protected $model = ComparableProperty::class;

    public function definition(): array
    {
        return [
            'valuation_id' => Valuation::factory(),
            'property_listing_id' => PropertyListing::factory(),
            'external_reference' => null,
            'address' => fake()->address(),
            'sale_price' => fake()->numberBetween(5000000, 150000000),
            'sale_date' => fake()->date(),
            'distance_km' => fake()->randomFloat(2, 0.2, 10),
            'similarity_score' => fake()->numberBetween(40, 95),
        ];
    }
}
