<?php

namespace Database\Factories;

use App\Models\AreaLandingPage;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AreaLandingPage> */
class AreaLandingPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'zone_id' => Zone::factory(),
            'headline' => $this->faker->sentence(4),
            'subheadline' => $this->faker->sentence(10),
            'content' => '<p>'.$this->faker->paragraph().'</p>',
            'features' => ['Metro access', 'Schools nearby', 'Shopping centers'],
            'is_active' => true,
        ];
    }
}
