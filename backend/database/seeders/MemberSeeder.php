<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Member::create([
                'nom' => 'Nom' . $i,
                'prenom' => 'Prenom' . $i,
                'email' => 'email' . $i . '@test.saas',
                'telephone' => '+221 77 ' . $i . '00 ' . $i . '00',
                'adresse' => 'Adresse ' . $i,
                'profession' => 'Profession ' . $i,
                'is_active' => true,
            ]);
        }
    }
}
