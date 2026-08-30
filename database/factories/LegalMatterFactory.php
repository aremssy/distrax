<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\LegalMatter;
use Illuminate\Database\Eloquent\Factories\Factory;

class LegalMatterFactory extends Factory
{
    protected $model = LegalMatter::class;

    public function definition(): array
    {
        return [
            'deal_id' => Deal::factory(),
            'type' => fake()->randomElement(['closing', 'title_search', 'contract_review']),
            'status' => 'pending',
            'assigned_to' => null,
            'due_date' => now()->addDays(7),
            'notes' => null,
            'resolved_at' => null,
        ];
    }
}
