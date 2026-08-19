<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenancy>
 */
class TenancyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'property_listing_id' => PropertyListing::factory(),
            'unit_id' => null,
            'tenant_id' => User::factory(),
            'tenant_name' => null,
            'tenant_phone' => null,
            'tenant_email' => null,
            'rent_amount' => fake()->numberBetween(5000, 50000),
            'deposit_amount' => fake()->numberBetween(0, 20000),
            'due_day_of_month' => fake()->numberBetween(1, 28),
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => null,
            'status' => 'active',
        ];
    }
}
