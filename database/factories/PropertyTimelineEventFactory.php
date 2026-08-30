<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\PropertyTimelineEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyTimelineEventFactory extends Factory
{
    protected $model = PropertyTimelineEvent::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'event_type' => fake()->randomElement(PropertyTimelineEvent::EVENT_TYPES),
            'description' => fake()->sentence(),
            'privacy_level' => 'public',
            'meta' => [],
            'occurred_at' => now(),
        ];
    }
}
