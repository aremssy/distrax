<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Currency> */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->currencyCode(),
            'name' => $this->faker->unique()->word(),
            'symbol' => '$',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'exchange_rate' => 1,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
