<?php

namespace Database\Factories;

use App\Enums\CycleStatus;
use App\Models\Tontine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cycle>
 */
class CycleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tontine_id' => Tontine::factory(),
            'number' => 1,
            'status' => CycleStatus::Open,
            'started_at' => now(),
            'closed_at' => null,
        ];
    }
}
