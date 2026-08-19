<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\TechnicianCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technician>
 */
class TechnicianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'technician_category_id' => TechnicianCategory::factory(),
            'zone_id' => null,
            'bio' => fake()->text(200),
            'skills' => ['Plumbing', 'Electrical'],
            'available_time' => ['morning', 'evening'],
            'experience_years' => fake()->numberBetween(1, 20),
            'hourly_rate' => fake()->numberBetween(500, 5000),
            'rating' => fake()->randomFloat(2, 3, 5),
            'is_available' => true,
            'is_verified' => true,
            'status' => 'active',
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'is_verified' => false]);
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }
}
