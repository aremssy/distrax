<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicianBooking>
 */
class TechnicianBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'technician_id' => Technician::factory(),
            'user_id' => User::factory(),
            'description' => fake()->paragraph(),
            'scheduled_at' => now()->addDays(3),
            'address' => fake()->address(),
            'is_urgent' => false,
            'agreed_amount' => null,
            'status' => 'pending',
        ];
    }

    public function quoted(): static
    {
        return $this->state(['status' => 'quoted']);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => 'accepted',
            'agreed_amount' => fake()->numberBetween(1000, 10000),
        ]);
    }
}
