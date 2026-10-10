<?php

namespace Database\Factories;

use App\Models\Contribution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ContributionFactory extends Factory
{
    protected $model = Contribution::class;

    public function definition(): array
    {
        return [
            'montant' => $this->faker->numberBetween(1000, 50000),
            'montant_initial' => $this->faker->numberBetween(5000, 50000),
            'date_paiement' => Carbon::now(),
            'date_echeance' => Carbon::now()->addDays(30),
            'statut' => $this->faker->randomElement(['en_attente', 'paye', 'retard']),
            'jours_delai' => 0,
        ];
    }
}
