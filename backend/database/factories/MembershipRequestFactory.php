<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipRequest>
 */
class MembershipRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tontine_id' => Tontine::factory(),
            'user_id' => User::factory(),
            'status' => RequestStatus::Pending,
            'decided_by' => null,
            'decided_at' => null,
            'reason' => null,
        ];
    }
}
