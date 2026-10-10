<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'reference' => 'PAY-' . strtoupper(substr($this->faker->unique()->string(16), 0, 12)),
            'methode' => $this->faker->randomElement(['cash', 'transfert', 'mobile_money', 'bank']),
            'montant' => $this->faker->numberBetween(1000, 50000),
            'date_paiement' => Carbon::now(),
        ];
    }
}
