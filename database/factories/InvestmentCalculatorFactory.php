<?php

namespace Database\Factories;

use App\Models\InvestmentCalculator;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentCalculatorFactory extends Factory
{
    protected $model = InvestmentCalculator::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'property_listing_id' => PropertyListing::factory(),
            'name' => 'buy_hold',
            'inputs' => ['purchase_price' => 50000000, 'rent_estimate' => 300000],
            'results' => ['gross_yield_pct' => 7.2, 'net_yield_pct' => 5.8],
        ];
    }
}
