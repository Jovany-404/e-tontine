<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Tontine;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ScheduleService
{
    public function create(array $data): Schedule
    {
        $schedule = Schedule::create($data);

        $this->logActivity($data['tontine_id'], 'create', 'Schedule créé', [
            'member_id' => $data['member_id'],
            'date_benefice' => $data['date_benefice'],
        ]);

        return $schedule;
    }

    public function update(Schedule $schedule, array $data): Schedule
    {
        $avant = $schedule->toArray();

        $schedule->update($data);

        $this->logActivity($schedule->tontine_id, 'update', 'Schedule modifié', $avant, $schedule->toArray());

        return $schedule;
    }

    public function generateForTontine(Tontine $tontine): void
    {
        $membres = $tontine->memberships()
            ->where('statut', 'actif')
            ->orderBy('position')
            ->get();

        if ($membres->isEmpty()) return;

        $dateBene = clone $tontine->date_debut;
        $intervalle = 30; // jours par cycle

        foreach ($membres as $index => $membership) {
            Schedule::create([
                'tontine_id' => $tontine->id,
                'position_beneficiaire' => $index + 1,
                'member_id' => $membership->member_id,
                'date_benefice' => $dateBene->addDays($intervalle * ($index + 1)),
                'montant_benefice' => $tontine->montant_total,
                'statut' => 'prevu',
            ]);
        }
    }

    public function getByTontine(int $tontineId): array
    {
        $schedules = Schedule::query()
            ->where('tontine_id', $tontineId)
            ->orderBy('position_beneficiaire', 'asc')
            ->get();

        return [
            'schedules' => $schedules->load('member')->toArray(),
            'count' => $schedules->count(),
        ];
    }

    public function getNextBeneficiary(int $tontineId): ?array
    {
        $schedule = Schedule::query()
            ->where('tontine_id', $tontineId)
            ->where('statut', 'prevu')
            ->orderBy('date_benefice', 'asc')
            ->first();

        return $schedule ? $schedule->load('member')->toArray() : null;
    }

    public function markAsRealise(Schedule $schedule): Schedule
    {
        $schedule->update(['statut' => 'realise']);

        $this->logActivity($schedule->tontine_id, 'validation', 'Benefice réalisé', [
            'member_id' => $schedule->member_id,
        ]);

        return $schedule;
    }

    private function logActivity(int $tontineId, string $action, string $description, array $avant = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'tontine_id' => $tontineId,
            'action' => $action,
            'entite' => 'Schedule',
            'description' => $description,
            'donnees_avant' => $avant,
            'ip_address' => request()->ip(),
        ]);
    }
}
