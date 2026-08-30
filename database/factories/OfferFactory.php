<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'buyer_id' => User::factory(),
            'amount' => fake()->numberBetween(5000000, 100000000),
            'currency_code' => 'NGN',
            'terms' => null,
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
