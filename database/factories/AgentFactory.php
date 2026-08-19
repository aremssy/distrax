<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Agent> */
class AgentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'title' => fake()->randomElement(['Senior Agent', 'Property Consultant', 'Leasing Agent']),
            'bio' => fake()->paragraph(),
            'phone' => fake()->phoneNumber(),
            'whatsapp' => null,
            'is_active' => true,
        ];
    }
}
