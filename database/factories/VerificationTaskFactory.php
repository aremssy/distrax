<?php

namespace Database\Factories;

use App\Models\VerificationCase;
use App\Models\VerificationTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationTaskFactory extends Factory
{
    protected $model = VerificationTask::class;

    public function definition(): array
    {
        return [
            'verification_case_id' => VerificationCase::factory(),
            'layer' => 'document_review',
            'owner_role' => 'distrax',
            'status' => 'not_started',
            'notes' => null,
            'assigned_to' => null,
            'completed_at' => null,
        ];
    }
}
