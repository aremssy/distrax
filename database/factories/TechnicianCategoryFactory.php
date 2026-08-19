<?php

namespace Database\Factories;

use App\Models\TechnicianCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TechnicianCategory>
 */
class TechnicianCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'icon' => null,
            'commission_rate' => fake()->numberBetween(0, 20),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
