<?php

namespace App\Jobs;

use App\Models\Membership;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPaymentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $memberships = Membership::query()
            ->where('statut', 'actif')
            ->with(['member', 'tontine', 'contributions'])
            ->get();

        foreach ($memberships as $membership) {
            if (!$membership->tontine || !$membership->tontine->notification_active) {
                continue;
            }

            $contribution = $membership->contributions()
                ->where('statut', 'en_attente')
                ->whereDate('date_echeance', '>=', now()->toDateString())
                ->orderBy('date_echeance', 'asc')
                ->first();

            if (!$contribution) {
                continue;
            }

            $daysLeft = now()->diffInDays(Carbon::parse($contribution->date_echeance), false);

            if ($daysLeft > 3) {
                continue;
            }

            $alreadySent = Notification::query()
                ->where('user_id', $membership->tontine->user_id)
                ->where('membership_id', $membership->id)
                ->where('contribution_id', $contribution->id)
                ->where('type', 'reminder')
                ->whereDate('created_at', today())
                ->exists();

            if ($alreadySent) {
                continue;
            }

            Notification::create([
                'user_id' => $membership->tontine->user_id,
                'membership_id' => $membership->id,
                'tontine_id' => $membership->tontine_id,
                'contribution_id' => $contribution->id,
                'type' => 'reminder',
                'titre' => 'Rappel de paiement',
                'message' => sprintf(
                    'Bonjour %s, votre contribution de %s FCU est attendue pour le %s. Montant restant: %s FCU',
                    $membership->member?->nom ?? 'membre',
                    $contribution->montant,
                    $contribution->date_echeance->format('d/m/Y'),
                    $membership->montant_restant
                ),
                'is_read' => false,
            ]);
        }
    }
}
