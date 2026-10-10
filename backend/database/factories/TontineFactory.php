<?php

namespace Database\Factories;

use App\Enums\Frequency;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\TontineStatus;
use App\Enums\TontineType;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tontine>
 */
class TontineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'manager_id' => User::factory(),
            'name' => 'Tontine '.fake()->lastName(),
            'description' => fake()->sentence(),
            'type' => TontineType::Family,
            'contribution_amount' => fake()->randomElement([5000, 10000, 20000, 50000]),
            'frequency' => Frequency::Monthly,
            'start_date' => now()->addWeek()->toDateString(),
            'expected_members' => fake()->numberBetween(5, 12),
            'rules' => null,
            'status' => TontineStatus::Draft,
        ];
    }

    /** Règle métier : le gérant est aussi membre de sa tontine. */
    public function configure(): static
    {
        return $this->afterCreating(function (Tontine $tontine) {
            $tontine->members()->create([
                'user_id' => $tontine->manager_id,
                'role' => MemberRole::Manager,
                'status' => MemberStatus::Active,
                'joined_at' => now(),
            ]);
        });
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TontineStatus::Active,
        ]);
    }
}
