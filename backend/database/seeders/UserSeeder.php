<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@tontine.saas',
                'password' => 'admin123',
                'nom' => 'Administrateur',
                'telephone' => '+221 77 000 00 00',
                'role' => 'admin',
            ],
            [
                'email' => 'test@tontine.saas',
                'password' => 'test123',
                'nom' => 'Utilisateur Test',
                'telephone' => '+221 77 111 11 11',
                'role' => 'admin',
            ],
            [
                'email' => 'manager@tontine.saas',
                'password' => 'manager123',
                'nom' => 'Responsable Tontine',
                'telephone' => '+221 77 222 22 22',
                'role' => 'manager',
            ],
            [
                'email' => 'finance@tontine.saas',
                'password' => 'finance123',
                'nom' => 'Comptable Demo',
                'telephone' => '+221 77 333 33 33',
                'role' => 'finance',
            ],
            [
                'email' => 'guest@tontine.saas',
                'password' => 'guest123',
                'nom' => 'Visiteur Demo',
                'telephone' => '+221 77 444 44 44',
                'role' => 'user',
            ],
        ];

        foreach ($users as $userData) {
            if (!User::where('email', $userData['email'])->exists()) {
                User::create([
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'nom' => $userData['nom'],
                    'telephone' => $userData['telephone'],
                    'role' => $userData['role'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
