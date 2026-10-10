<?php

namespace App\Jobs;

use App\Models\Tontine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateContributionSchedules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $tontines = Tontine::query()
            ->where('statut', '!=', 'archivee')
            ->with('memberships')
            ->get();

        foreach ($tontines as $tontine) {
            $memberships = $tontine->memberships()
                ->where('statut', 'actif')
                ->orderBy('position')
                ->get();

            if ($memberships->isEmpty()) {
                continue;
            }

            $tontine->schedules()->delete();

            $startDate = clone $tontine->date_debut;
            $interval = $tontine->nombre_cycles > 0 && $tontine->date_fin
                ? $tontine->date_fin->diffInDays($tontine->date_debut) / $tontine->nombre_cycles
                : 30;

            foreach ($memberships as $index => $membership) {
                $tontine->schedules()->create([
                    'position_beneficiaire' => $index + 1,
                    'member_id' => $membership->member_id,
                    'date_benefice' => $startDate->addDays($interval * ($index + 1)),
                    'montant_benefice' => $tontine->montant_total,
                    'statut' => 'prevu',
                ]);
            }
        }
    }
}
