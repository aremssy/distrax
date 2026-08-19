<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => null,
            'phone' => fake()->unique()->numerify('01#########'),
            'verification_status' => 'unverified',
            'language' => 'en',
            'currency' => 'BDT',
            'avatar' => null,
            'phone_visibility' => 'everyone',
            'is_blocked' => false,
            // Nullable columns the app reads — set so factory users are attribute-complete
            // under Model::shouldBeStrict() (missing attributes throw).
            'verification_document_path' => null,
            'verification_reviewed_by' => null,
            'verification_note' => null,
            'verification_reviewed_at' => null,
            'social_provider' => null,
            'social_id' => null,
            'deletion_requested_at' => null,
            'last_active_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
