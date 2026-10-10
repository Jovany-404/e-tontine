<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\ContributionSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'schedule_id' => ContributionSchedule::factory(),
            'amount' => 10000,
            'method' => PaymentMethod::Cash,
            'reference' => null,
            'note' => null,
            'recorded_by' => User::factory(),
            'paid_at' => now()->toDateString(),
        ];
    }
}
