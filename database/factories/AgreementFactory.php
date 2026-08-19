<?php

namespace Database\Factories;

use App\Models\Agreement;
use App\Models\Tenancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agreement>
 */
class AgreementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenancy_id' => Tenancy::factory(),
            'agreement_template_id' => null,
            'content' => fake()->paragraphs(3, true),
            'status' => 'draft',
            'generated_at' => now(),
        ];
    }
}
