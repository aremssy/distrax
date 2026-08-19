<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question' => rtrim(fake()->sentence(), '.').'?',
            'answer' => fake()->paragraph(),
            'category' => fake()->randomElement(['General', 'Payments', 'Listings', 'Account']),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
