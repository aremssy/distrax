<?php

namespace Database\Factories;

use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerTransaction>
 */
class LedgerTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'property_listing_id' => null,
            'tenancy_id' => null,
            'type' => fake()->randomElement(['income', 'expense']),
            'category' => fake()->randomElement(['rent', 'maintenance', 'utilities', 'other']),
            'amount' => fake()->numberBetween(500, 50000),
            'occurred_on' => now()->toDateString(),
            'description' => fake()->sentence(),
        ];
    }
}
