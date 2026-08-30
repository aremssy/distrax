<?php

namespace Database\Factories;

use App\Models\PropertyListing;
use App\Models\VerificationCase;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationCaseFactory extends Factory
{
    protected $model = VerificationCase::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'status' => 'in_progress',
            'opened_at' => now(),
            'closed_at' => null,
            'expiry_review_date' => null,
            'notes' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function resolved(string $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'closed_at' => now(),
            'expiry_review_date' => now()->addYear()->toDateString(),
        ]);
    }
}
