<?php

namespace Database\Factories;

use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'position' => $this->faker->numberBetween(1, 20),
            'montant_engage' => $this->faker->numberBetween(5000, 50000),
            'montant_paye' => 0,
            'montant_restant' => $this->faker->numberBetween(5000, 50000),
            'statut' => 'actif',
            'date_inscription' => now(),
        ];
    }
}
