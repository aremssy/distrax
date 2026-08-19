<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'technician_booking_id' => TechnicianBooking::factory(),
            'technician_id' => Technician::factory(),
            'amount' => fake()->numberBetween(1000, 10000),
            'description' => fake()->sentence(),
            'valid_until' => now()->addDays(3),
            'status' => 'pending',
        ];
    }

    public function accepted(): static
    {
        return $this->state(['status' => 'accepted']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }
}
