<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyListing>
 */
class PropertyListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone_id' => Zone::factory(),
            'owner_id' => User::factory(),
            'type' => fake()->randomElement(['rent', 'sale', 'hotel', 'mess', 'land']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'language_tag' => 'en',
            'price' => fake()->numberBetween(10000, 5000000),
            'service_charge' => fake()->numberBetween(0, 10000),
            'advance_months' => fake()->numberBetween(0, 3),
            'bedrooms' => fake()->numberBetween(1, 6),
            'bathrooms' => fake()->numberBetween(1, 4),
            'floor' => fake()->numberBetween(1, 15),
            'total_floors' => fake()->numberBetween(1, 20),
            'area_sqft' => fake()->numberBetween(500, 5000),
            'parking' => fake()->boolean(),
            'furnished' => fake()->boolean(),
            'allowed_for' => fake()->randomElement(['bachelor', 'family', 'both']),
            'utility_flags' => [],
            'address' => fake()->address(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'status' => 'active',
            'is_featured' => false,
            'is_verified' => false,
            'last_freshness_check_at' => now(),
            'needs_confirmation_at' => now()->addMonth(),
            'published_at' => now(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'published_at' => null,
        ]);
    }
}
