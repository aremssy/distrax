<?php

namespace Database\Factories;

use App\Models\AskDistraxQuery;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AskDistraxQueryFactory extends Factory
{
    protected $model = AskDistraxQuery::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => null,
            'query' => fake()->sentence(),
            'response' => null,
            'context' => ['property_listing_id' => null],
            'latency_ms' => null,
            'was_helpful' => null,
        ];
    }
}
