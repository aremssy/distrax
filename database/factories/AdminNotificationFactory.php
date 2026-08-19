<?php

namespace Database\Factories;

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdminNotification> */
class AdminNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => User::factory(),
            'type' => $this->faker->randomElement(array_keys(AdminNotification::TYPE_META)),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->sentence(8),
            'link' => null,
            'is_read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true]);
    }
}
