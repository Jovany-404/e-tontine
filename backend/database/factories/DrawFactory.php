<?php

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Draw>
 */
class DrawFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'tontine_id' => Tontine::factory(),
            'round' => 1,
            'winner_member_id' => TontineMember::factory(),
            'amount' => 50000,
            'drawn_by' => User::factory(),
            'drawn_at' => now(),
        ];
    }
}
