<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        if (Testimonial::exists()) {
            return;
        }

        Testimonial::factory()->count(6)->sequence(
            ['sort_order' => 0],
            ['sort_order' => 1],
            ['sort_order' => 2],
            ['sort_order' => 3],
            ['sort_order' => 4],
            ['sort_order' => 5],
        )->create();
    }
}
