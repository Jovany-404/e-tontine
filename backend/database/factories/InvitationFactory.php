<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tontine_id' => Tontine::factory(),
            'created_by' => User::factory(),
            'token_hash' => Invitation::hashToken(Invitation::generateToken()),
            'expires_at' => now()->addDays(7),
            'is_active' => true,
        ];
    }

    /** Permet de connaître le token en clair dans les tests. */
    public function forToken(string $plainToken): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => Invitation::hashToken($plainToken),
        ]);
    }
}
