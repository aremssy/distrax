<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->company().' '.fake()->randomElement(['Residence', 'Heights', 'Towers', 'Gardens']);

        return [
            'zone_id' => Zone::factory(),
            'developer_name' => fake()->company(),
            'title' => $title,
            'description' => fake()->paragraph(3),
            'price_from' => fake()->numberBetween(50000, 500000),
            'status' => fake()->randomElement(['upcoming', 'ongoing', 'completed']),
            'cover_image' => null,
            'completion_date' => fake()->dateTimeBetween('now', '+2 years'),
            'is_featured' => true,
            'sort_order' => 0,
        ];
    }
}
