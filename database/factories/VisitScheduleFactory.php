<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\User;
use App\Models\VisitSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VisitSchedule> */
class VisitScheduleFactory extends Factory
{
    public function definition(): array
    {
        return ['property_listing_id' => PropertyListing::factory(), 'user_id' => User::factory(), 'scheduled_at' => now()->addDay(), 'status' => 'pending', 'note' => fake()->sentence()];
    }
}
