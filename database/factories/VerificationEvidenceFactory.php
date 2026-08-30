<?php

namespace Database\Factories;

use App\Models\VerificationEvidence;
use App\Models\VerificationTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationEvidenceFactory extends Factory
{
    protected $model = VerificationEvidence::class;

    public function definition(): array
    {
        return [
            'verification_task_id' => VerificationTask::factory(),
            'type' => 'document',
            'file_path' => 'verification-evidence/'.fake()->uuid().'.pdf',
            'description' => fake()->sentence(),
            'uploaded_by' => null,
            'metadata' => [],
        ];
    }
}
