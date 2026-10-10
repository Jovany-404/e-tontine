<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\Membership;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ContributionService
{
    public function register(Membership $membership, array $data): Contribution
    {
        $contribution = Contribution::create([
            'membership_id' => $membership->id,
            'tontine_id' => $membership->tontine_id,
            'montant' => $data['montant'],
            'montant_initial' => $membership->montant_engage,
            'date_paiement' => $data['date_paiement'],
            'date_echeance' => $data['date_echeance'],
            'statut' => $data['statut'] ?? 'en_attente',
            'commentaire' => $data['commentaire'] ?? null,
        ]);

        // Update membership
        $this->updateMembershipPayment($membership, $data['montant']);

        $this->logActivity($membership->tontine_id, 'payement', 'Contribution enregistrée', [
            'member_id' => $membership->member_id,
            'montant' => $data['montant'],
        ]);

        return $contribution;
    }

    public function validate(Contribution $contribution, array $data): Contribution
    {
        $avant = $contribution->toArray();

        $contribution->update([
            'statut' => 'paye',
            'date_paiement' => $data['date_paiement'] ?? now(),
        ]);

        // Update membership
        $membership = $contribution->membership;
        $this->updateMembershipPayment($membership, $contribution->montant);

        $this->logActivity($contribution->tontine_id, 'validation', 'Contribution validée', $avant, $contribution->toArray());

        return $contribution;
    }

    public function updateLateStatus(Contribution $contribution): Contribution
    {
        $echeance = Carbon::parse($contribution->date_echeance);
        $aujourdHui = Carbon::now();  // ← CORRECTION: removed apostrophe

        if ($aujourdHui > $echeance && $contribution->statut !== 'paye') {
            $joursDelai = $aujourdHui->diffInDays($echeance);

            $contribution->update([
                'statut' => 'retard',
                'jours_delai' => $joursDelai,
            ]);

            return $contribution;
        }

        return $contribution;
    }

    public function getByMembership(Membership $membership): array
    {
        $contributions = $membership->contributions()
            ->orderBy('date_echeance', 'desc')
            ->get();

        $totalPaye = $contributions->where('statut', 'paye')->sum('montant');
        $totalRetard = $contributions->where('statut', 'retard')->sum('montant');
        $totalEnAttente = $contributions->where('statut', 'en_attente')->sum('montant');

        return [
            'contributions' => $contributions->toArray(),
            'total_paye' => $totalPaye,
            'total_retard' => $totalRetard,
            'total_en_attente' => $totalEnAttente,
            'count' => $contributions->count(),
        ];
    }

    public function getByTontine(int $tontineId, string $statut = null): array
    {
        $query = Contribution::query()->where('tontine_id', $tontineId);

        if ($statut) {
            $query->where('statut', $statut);
        }

        $contributions = $query->orderBy('date_echeance', 'desc')->get();

        return [
            'contributions' => $contributions->toArray(),
            'total' => $contributions->sum('montant'),
            'count' => $contributions->count(),
        ];
    }

    private function updateMembershipPayment(Membership $membership, float $montant): void
    {
        $avant = $membership->montant_paye;
        $nouveau = $avant + $montant;
        $restant = $membership->montant_engage - $nouveau;

        $membership->update([
            'montant_paye' => $nouveau,
            'montant_restant' => max(0, $restant),
            'date_dernier_paiement' => now(),
            'statut' => $nouveau >= $membership->montant_engage ? 'actif' : 'actif',
        ]);
    }

    private function logActivity(int $tontineId, string $action, string $description, array $avant = null, array $apres = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'tontine_id' => $tontineId,
            'action' => $action,
            'entite' => 'Contribution',
            'description' => $description,
            'donnees_avant' => $avant,
            'ip_address' => request()->ip(),
        ]);
    }
}
