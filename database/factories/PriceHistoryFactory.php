<?php

namespace Database\Factories;

use App\Models\PriceHistory;
use App\Models\PropertyListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'old_price' => fake()->numberBetween(5000000, 50000000),
            'new_price' => fake()->numberBetween(5000000, 50000000),
            'changed_by' => null,
            'currency_code' => 'NGN',
            'changed_at' => now(),
        ];
    }
}
