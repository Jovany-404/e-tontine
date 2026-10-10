<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'nom' => $this->faker->firstName(),
            'telephone' => $this->faker->phoneNumber(),
            'role' => 'admin',
            'is_active' => true,
        ];
    }
}
