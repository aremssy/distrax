<?php

namespace Database\Factories;

use App\Models\RentPayment;
use App\Models\Tenancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentPayment>
 */
class RentPaymentFactory extends Factory
{
    public function definition(): array
    {
        $periodStart = now()->startOfMonth();

        return [
            'tenancy_id' => Tenancy::factory(),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodStart->copy()->endOfMonth()->toDateString(),
            'due_date' => $periodStart->copy()->addDays(4)->toDateString(),
            'amount' => fake()->numberBetween(5000, 50000),
            'status' => 'pending',
            'paid_at' => null,
        ];
    }
}
