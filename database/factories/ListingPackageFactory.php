<?php

namespace Database\Factories;

use App\Models\ListingPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ListingPackage>
 */
class ListingPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'price' => fake()->numberBetween(500, 5000),
            'post_quota' => fake()->numberBetween(1, 20),
            'duration_days' => fake()->randomElement([7, 30, 90]),
            'features' => [],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
