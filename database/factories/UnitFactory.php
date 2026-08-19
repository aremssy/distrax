<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'name' => 'Unit '.fake()->bothify('#?'),
            'floor' => (string) fake()->numberBetween(1, 10),
            'rent_amount' => fake()->numberBetween(5000, 50000),
            'status' => 'vacant',
            'notes' => null,
        ];
    }
}
