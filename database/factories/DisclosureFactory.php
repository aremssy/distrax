<?php

namespace Database\Factories;

use App\Models\Disclosure;
use App\Models\PropertyListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisclosureFactory extends Factory
{
    protected $model = Disclosure::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'category' => fake()->randomElement(['structural', 'legal', 'environmental', 'title']),
            'description' => fake()->sentence(),
            'document_path' => null,
            'acknowledged_at' => null,
        ];
    }
}
