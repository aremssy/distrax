<?php

namespace Database\Factories;

use App\Models\PropertyImage;
use App\Models\PropertyListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropertyImage> */
class PropertyImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'path' => 'https://picsum.photos/seed/'.fake()->uuid().'/800/600',
            'disk' => 'remote',
            'is_cover' => false,
            'sort_order' => 0,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (): array => ['is_cover' => true]);
    }
}
