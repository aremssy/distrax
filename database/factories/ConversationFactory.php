<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conversation> */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return ['property_listing_id' => PropertyListing::factory(), 'starter_id' => User::factory(), 'recipient_id' => User::factory(), 'last_message_at' => now()];
    }
}
