<?php

namespace Database\Factories;

use App\Enums\IdentityStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'identity_status' => IdentityStatus::Verified,
            'phone' => fake()->numerify('+221 77 ### ## ##'),
            'phone_verified_at' => null,
            'city' => fake()->city(),
            // Mot de passe de développement : Password123
            'password' => static::$password ??= Hash::make('Password123'),
            'remember_token' => Str::random(10),
        ];
    }

    /** Utilisateur dont l'email n'est pas vérifié. */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
