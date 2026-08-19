<?php

namespace Database\Factories;

use App\Models\Compare;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Compare> */
class CompareFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'property_listing_id' => PropertyListing::factory()];
    }
}
