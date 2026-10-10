<?php

namespace Database\Factories;

use App\Enums\ScheduleStatus;
use App\Models\Cycle;
use App\Models\TontineMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContributionSchedule>
 */
class ContributionScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'round' => 1,
            'tontine_member_id' => TontineMember::factory(),
            'due_date' => now()->addDays(7)->toDateString(),
            'amount_expected' => 10000,
            'amount_paid' => 0,
            'status' => ScheduleStatus::Pending,
            'paid_at' => null,
        ];
    }
}
