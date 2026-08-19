<?php

namespace Database\Factories;

use App\Models\RentPayment;
use App\Models\RentReceipt;
use App\Models\Tenancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentReceipt>
 */
class RentReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rent_payment_id' => RentPayment::factory(),
            'tenancy_id' => Tenancy::factory(),
            'receipt_number' => 'RCPT-'.fake()->unique()->numerify('######'),
            'amount' => fake()->numberBetween(5000, 50000),
            'issued_at' => now(),
            'notes' => null,
        ];
    }
}
