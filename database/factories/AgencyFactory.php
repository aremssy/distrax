<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Agency> */
class AgencyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_id' => User::factory(),
            'zone_id' => Zone::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'logo' => null,
            'description' => fake()->paragraph(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'address' => fake()->address(),
            'is_verified' => true,
            'status' => 'active',
        ];
    }
}
