<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reviewer_id' => User::factory(),
            'reviewable_type' => PropertyListing::class,
            'reviewable_id' => PropertyListing::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'body' => fake()->paragraph(),
            'is_verified' => true,
            'is_visible' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => ['is_visible' => false]);
    }
}
