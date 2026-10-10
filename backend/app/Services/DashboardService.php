<?php

namespace App\Services;

use App\Models\Tontine;
use App\Models\Membership;
use App\Models\Contribution;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function getAdminDashboard(): array
    {
        $userId = Auth::id();

        $tontines = Tontine::query()->where('user_id', $userId)->get();
        $tontinesActives = $tontines->where('statut', 'active')->count();
        $tontinesEnAttente = $tontines->where('statut', 'en_attente')->count();

        $totalMembres = $tontines->sum('nombre_membres');
        $totalColle = $tontines->sum(function ($t) {
            return $t->montant_total_paye;
        });
        $totalExpected = $tontines->sum('montant_total');

        $membresRetard = Membership::query()
            ->whereHas('tontine', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where('statut', 'retard')
            ->count();

        $recentActivities = ActivityLog::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_tontines' => $tontines->count(),
            'tontines_actives' => $tontinesActives,
            'tontines_en_attente' => $tontinesEnAttente,
            'total_membres' => $totalMembres,
            'total_colle' => $totalColle,
            'total_expected' => $totalExpected,
            'membres_retard' => $membresRetard,
            'recent_activities' => $recentActivities->toArray(),
            'progression_global' => $totalExpected > 0 ? round($totalColle / $totalExpected * 100, 2) : 0,
        ];
    }

    public function getTontineDashboard(int $tontineId): array
    {
        $tontine = Tontine::find($tontineId);

        if (!$tontine) {
            throw new \Exception('Tontine non trouvée');
        }

        $stats = [
            'tontine' => $tontine->toArray(),
            'total_membres' => $tontine->nombre_membres,
            'membres_actifs' => $tontine->memberships()->where('statut', 'actif')->count(),
            'membres_paye' => $tontine->nombre_membres_paye,
            'membres_retard' => $tontine->nombre_membres_retard,
            'total_colle' => $tontine->montant_total_paye,
            'total_expected' => $tontine->montant_total,
            'total_restant' => $tontine->montant_total_restant,
            'progression' => $tontine->progression,
        ];

        $recentContributions = Contribution::query()
            ->where('tontine_id', $tontineId)
            ->orderBy('date_paiement', 'desc')
            ->limit(5)
            ->get();

        $stats['recent_contributions'] = $recentContributions->toArray();

        $upcomingBeneficiaries = $tontine->schedules()
            ->where('statut', 'prevu')
            ->orderBy('date_benefice', 'asc')
            ->limit(3)
            ->get();

        $stats['upcoming_beneficiaries'] = $upcomingBeneficiaries->load('member')->toArray();

        return $stats;
    }
}
