<?php

namespace Database\Factories;

use App\Models\PropertyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyDocumentFactory extends Factory
{
    protected $model = PropertyDocument::class;

    public function definition(): array
    {
        return [
            'documentable_type' => 'App\\Models\\PropertyListing',
            'documentable_id' => 1,
            'uploaded_by' => User::factory(),
            'type' => 'title_deed',
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'is_verified' => false,
            'visibility_level' => 'internal_only',
            'metadata' => [],
        ];
    }
}
