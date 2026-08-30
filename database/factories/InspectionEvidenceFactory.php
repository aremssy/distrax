<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\InspectionEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

class InspectionEvidenceFactory extends Factory
{
    protected $model = InspectionEvidence::class;

    public function definition(): array
    {
        return [
            'inspection_id' => Inspection::factory(),
            'type' => fake()->randomElement(['photo', 'video', 'note']),
            'file_path' => 'inspection-evidence/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(),
        ];
    }
}
