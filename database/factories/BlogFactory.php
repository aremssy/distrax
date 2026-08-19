<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Blog> */
class BlogFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);

        return [
            'author_id' => User::factory(),
            'blog_category_id' => BlogCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 999999),
            'content' => '<p>'.$this->faker->paragraphs(3, true).'</p>',
            'excerpt' => $this->faker->sentence(15),
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }
}
