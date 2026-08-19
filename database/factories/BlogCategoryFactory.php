<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BlogCategory> */
class BlogCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
