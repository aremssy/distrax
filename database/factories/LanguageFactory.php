<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Language> */
class LanguageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->languageCode(),
            'name' => $this->faker->unique()->word(),
            'native_name' => $this->faker->word(),
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
        ];
    }

    public function rtl(): static
    {
        return $this->state(['direction' => 'rtl']);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
