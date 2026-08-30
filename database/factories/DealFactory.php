<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Offer;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        $listing = PropertyListing::factory()->create();
        $seller = $listing->owner_id ?: User::factory()->create()->id;
        $offer = Offer::factory()->create([
            'property_listing_id' => $listing->id,
        ]);

        return [
            'property_listing_id' => $listing->id,
            'offer_id' => $offer->id,
            'buyer_id' => $offer->buyer_id,
            'seller_id' => $seller,
            'agreed_amount' => $listing->price,
            'currency_code' => 'NGN',
            'stage' => 'offer_accepted',
            'closed_at' => null,
        ];
    }

    public function stage(string $stage): static
    {
        return $this->state(fn (): array => ['stage' => $stage]);
    }
}
