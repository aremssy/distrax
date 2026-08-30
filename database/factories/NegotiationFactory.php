<?php

namespace Database\Factories;

use App\Models\Negotiation;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NegotiationFactory extends Factory
{
    protected $model = Negotiation::class;

    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'sender_id' => User::factory(),
            'amount' => fake()->numberBetween(5000000, 100000000),
            'message' => fake()->sentence(),
        ];
    }
}
