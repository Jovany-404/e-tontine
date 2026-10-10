<?php

namespace App\Services;

use App\Models\Tontine;
use App\Models\Membership;
use App\Models\Contribution;
use App\Models\Payment;
use Carbon\Carbon;

class ReportService
{
    public function getTontineReport(int $tontineId): array
    {
        $tontine = Tontine::find($tontineId);

        $membres = $tontine->memberships()
            ->where('statut', 'actif')
            ->with('member')
            ->get();

        $contributions = Contribution::query()
            ->where('tontine_id', $tontineId)
            ->get();

        $payments = Payment::query()
            ->whereHas('contribution', function ($q) use ($tontineId) {
                $q->where('tontine_id', $tontineId);
            })
            ->get();

        return [
            'tontine' => $tontine->toArray(),
            'statistiques' => [
                'total_membres' => $membres->count(),
                'total_paye' => $membres->sum('montant_paye'),
                'total_restant' => $membres->sum('montant_restant'),
                'total_contributions' => $contributions->count(),
                'total_paiements' => $payments->count(),
                'montant_total_paiements' => $payments->sum('montant'),
            ],
            'membres' => $membres->toArray(),
            'contributions' => $contributions->toArray(),
            'paiements' => $payments->toArray(),
        ];
    }

    public function getMonthlyReport(int $tontineId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->endOfMonth();

        $contributions = Contribution::query()
            ->where('tontine_id', $tontineId)
            ->whereBetween('date_paiement', [$startDate, $endDate])
            ->get();

        $payments = Payment::query()
            ->whereHas('contribution', function ($q) use ($tontineId) {
                $q->where('tontine_id', $tontineId);
            })
            ->whereBetween('date_paiement', [$startDate, $endDate])
            ->get();

        return [
            'periode' => [
                'mois' => $month,
                'année' => $year,
                'debut' => $startDate->format('Y-m-d'),
                'fin' => $endDate->format('Y-m-d'),
            ],
            'contributions' => [
                'count' => $contributions->count(),
                'total' => $contributions->sum('montant'),
                'byStatut' => [
                    'paye' => $contributions->where('statut', 'paye')->sum('montant'),
                    'retard' => $contributions->where('statut', 'retard')->sum('montant'),
                    'en_attente' => $contributions->where('statut', 'en_attente')->sum('montant'),
                ],
            ],
            'paiements' => [
                'count' => $payments->count(),
                'total' => $payments->sum('montant'),
                'byMethode' => $payments->groupBy('methode')->map(function ($group) {
                    return $group->sum('montant');
                })->toArray(),
            ],
        ];
    }

    public function getMemberReport(int $membershipId): array
    {
        $membership = Membership::find($membershipId);

        $contributions = $membership->contributions()->get();
        $payments = $membership->payments()->get();

        return [
            'member' => $membership->member->toArray(),
            'tontine' => $membership->tontine->toArray(),
            'engagement' => [
                'montant_engage' => $membership->montant_engage,
                'montant_paye' => $membership->montant_paye,
                'montant_restant' => $membership->montant_restant,
                'progression' => $membership->progression,
            ],
            'contributions' => [
                'count' => $contributions->count(),
                'total' => $contributions->sum('montant'),
                'details' => $contributions->toArray(),
            ],
            'paiements' => [
                'count' => $payments->count(),
                'total' => $payments->sum('montant'),
                'details' => $payments->toArray(),
            ],
        ];
    }
}
