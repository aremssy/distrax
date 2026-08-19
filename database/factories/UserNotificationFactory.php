<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserNotification> */
class UserNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'new_message',
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->sentence(10),
            'is_read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true, 'read_at' => now()]);
    }
}
