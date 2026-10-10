<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TontineMember>
 */
class TontineMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tontine_id' => Tontine::factory(),
            'user_id' => User::factory(),
            'role' => MemberRole::Member,
            'status' => MemberStatus::Active,
            'joined_at' => now(),
        ];
    }
}
