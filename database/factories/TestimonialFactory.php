<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Testimonial> */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => fake()->randomElement(['Tenant', 'Property Owner', 'Buyer', 'Agent']),
            'quote' => fake()->paragraph(2),
            'rating' => fake()->numberBetween(4, 5),
            'avatar' => null,
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
