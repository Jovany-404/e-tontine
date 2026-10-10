<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tontine;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Schedule;
use App\Models\Contribution;
use App\Models\Payment;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TontineSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('No user found. Run UserSeeder first.');
            return;
        }

        if (Tontine::where('user_id', $user->id)->exists()) {
            return;
        }

        $tontine = Tontine::create([
            'user_id' => $user->id,
            'nom' => 'Tontine de Collecte Demo',
            'description' => 'Une tontine fictive avec données complètes, paiements partiels et retards.',
            'montant_total' => 100000,
            'montant_membre' => 10000,
            'nombre_membres' => 0,
            'nombre_cycles' => 10,
            'date_debut' => Carbon::now()->subWeeks(4),
            'date_fin' => Carbon::now()->addMonths(9),
            'statut' => 'active',
            'type_rotation' => 'chrono',
            'delai_paiement_jours' => 5,
            'notification_active' => true,
            'montant_total_paye' => 0,
            'montant_total_restant' => 0,
            'nombre_membres_paye' => 0,
            'nombre_membres_retard' => 0,
            'progression' => 0,
        ]);

        $memberships = [];
        $members = [];

        for ($i = 1; $i <= 10; $i++) {
            $member = Member::create([
                'nom' => 'Donné' . $i,
                'prenom' => 'Client' . $i,
                'email' => 'client' . $i . '@demo.tontine',
                'telephone' => '+221 77 ' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' ' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'adresse' => 'Quartier ' . $i . ', Dakar',
                'profession' => ['Agent', 'Comptable', 'Agriculteur', 'Boutiquier', 'Ingénieur'][$i % 5],
                'is_active' => true,
            ]);

            $isPaid = $i <= 6;
            $isLate = in_array($i, [8, 9, 10], true);
            $paidAmount = $isPaid ? rand(2500, 10000) : 0;
            $remaining = max(0, 10000 - $paidAmount);
            $status = $isLate ? 'retard' : 'actif';
            $progression = $paidAmount > 0 ? round($paidAmount / 10000 * 100, 2) : 0;

            $membership = Membership::create([
                'tontine_id' => $tontine->id,
                'member_id' => $member->id,
                'position' => $i,
                'montant_engage' => 10000,
                'montant_paye' => $paidAmount,
                'montant_restant' => $remaining,
                'statut' => $status,
                'date_inscription' => Carbon::now()->subDays(rand(1, 45)),
                'date_dernier_paiement' => $paidAmount > 0 ? Carbon::now()->subDays(rand(1, 20)) : null,
                'progression' => $progression,
            ]);

            $memberships[] = $membership;
            $members[] = $member;

            $contributionDate = $isLate ? Carbon::now()->subDays(rand(5, 20)) : Carbon::now()->addDays(rand(2, 14));
            $contributionStatus = $paidAmount > 0 ? 'paye' : ($isLate ? 'retard' : 'en_attente');

            $contribution = Contribution::create([
                'membership_id' => $membership->id,
                'tontine_id' => $tontine->id,
                'montant' => $paidAmount,
                'montant_initial' => 10000,
                'date_paiement' => $paidAmount > 0 ? Carbon::now()->subDays(rand(1, 20)) : null,
                'date_echeance' => $contributionDate,
                'statut' => $contributionStatus,
                'jours_delai' => $isLate ? rand(3, 12) : 0,
                'commentaire' => $paidAmount > 0 ? 'Paiement reçu et validé.' : ($isLate ? 'Paiement en retard' : 'Paiement en attente.'),
            ]);

            if ($paidAmount > 0) {
                Payment::create([
                    'contribution_id' => $contribution->id,
                    'reference' => Str::upper(substr((string) Str::uuid(), 0, 12)),
                    'methode' => 'Transfert',
                    'organisme' => 'Mobile Money',
                    'montant' => $paidAmount,
                    'date_paiement' => $contribution->date_paiement,
                    'commentaire' => 'Paiement partiel de démonstration',
                    'validated_by' => $user->id,
                    'date_validation' => Carbon::now()->subDays(rand(1, 5)),
                ]);
            }
        }

        $beneficiaryDate = Carbon::parse($tontine->date_debut);

        for ($cycle = 1; $cycle <= $tontine->nombre_cycles; $cycle++) {
            $beneficiaryIndex = ($cycle - 1) % count($members);

            Schedule::create([
                'tontine_id' => $tontine->id,
                'position_beneficiaire' => $cycle,
                'member_id' => $members[$beneficiaryIndex]->id,
                'date_benefice' => $beneficiaryDate->addMonth(),
                'montant_benefice' => 10000,
                'statut' => 'prevu',
            ]);
        }

        $montantTotalPaye = array_sum(array_map(fn ($membership) => $membership->montant_paye, $memberships));
        $montantTotalRestant = array_sum(array_map(fn ($membership) => $membership->montant_restant, $memberships));
        $nombreMembresPaye = count(array_filter($memberships, fn ($membership) => $membership->montant_paye > 0));
        $nombreMembresRetard = count(array_filter($memberships, fn ($membership) => $membership->statut === 'retard'));
        $progression = $tontine->montant_total > 0 ? round($montantTotalPaye / $tontine->montant_total * 100, 2) : 0;

        $tontine->update([
            'nombre_membres' => count($memberships),
            'montant_total_paye' => $montantTotalPaye,
            'montant_total_restant' => $montantTotalRestant,
            'nombre_membres_paye' => $nombreMembresPaye,
            'nombre_membres_retard' => $nombreMembresRetard,
            'progression' => $progression,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'tontine_id' => $tontine->id,
            'member_id' => $members[0]->id,
            'membership_id' => $memberships[0]->id,
            'action' => 'creation',
            'entite' => 'tontine',
            'entite_id' => $tontine->id,
            'description' => 'Tontine de démonstration créée avec 10 membres et paiements mixtes.',
            'donnees_avant' => null,
            'donnees_apres' => json_encode(['tontine' => $tontine->toArray()]),
            'ip_address' => '127.0.0.1',
        ]);
    }
}
