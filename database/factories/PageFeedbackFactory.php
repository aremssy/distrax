<?php

namespace Database\Factories;

use App\Models\PageFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageFeedback>
 */
class PageFeedbackFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page' => 'faq',
            'is_helpful' => fake()->boolean(),
            'user_id' => null,
            'visitor_hash' => fake()->sha256(),
        ];
    }
}
