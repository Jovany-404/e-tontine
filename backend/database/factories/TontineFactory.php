<?php

namespace Database\Factories;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class TontineFactory extends Factory
{
    protected $model = Tontine::class;

    public function definition(): array
    {
        $user = User::first();

        return [
            'user_id' => $user ? $user->id : 1,
            'nom' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'montant_total' => $this->faker->numberBetween(50000, 500000),
            'montant_membre' => $this->faker->numberBetween(5000, 50000),
            'nombre_membres' => 0,
            'nombre_cycles' => $this->faker->numberBetween(5, 12),
            'date_debut' => Carbon::now(),
            'date_fin' => Carbon::now()->addMonths(12),
            'statut' => 'active',
            'type_rotation' => 'chrono',
            'delai_paiement_jours' => 5,
            'notification_active' => true,
        ];
    }
}
