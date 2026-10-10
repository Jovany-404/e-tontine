<?php

namespace App\Jobs;

use App\Models\Contribution;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateLateContributions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $contributions = Contribution::query()
            ->where('statut', '!=', 'paye')
            ->whereDate('date_echeance', '<', now()->toDateString())
            ->with(['membership.member', 'tontine'])
            ->get();

        foreach ($contributions as $contribution) {
            $daysLate = now()->diffInDays(Carbon::parse($contribution->date_echeance));

            $contribution->update([
                'statut' => 'retard',
                'jours_delai' => $daysLate,
            ]);

            if ($contribution->membership) {
                $contribution->membership->update([
                    'statut' => 'retard',
                ]);
            }

            if ($contribution->tontine && $contribution->tontine->notification_active) {
                $alreadySent = Notification::query()
                    ->where('user_id', $contribution->tontine->user_id)
                    ->where('membership_id', $contribution->membership_id)
                    ->where('contribution_id', $contribution->id)
                    ->where('type', 'alert')
                    ->whereDate('created_at', today())
                    ->exists();

                if (!$alreadySent) {
                    Notification::create([
                        'user_id' => $contribution->tontine->user_id,
                        'membership_id' => $contribution->membership_id,
                        'tontine_id' => $contribution->tontine_id,
                        'contribution_id' => $contribution->id,
                        'type' => 'alert',
                        'titre' => 'Contribution en retard',
                        'message' => sprintf(
                            'Attention: La contribution de %s FCU est en retard de %d jours.',
                            $contribution->montant,
                            $daysLate
                        ),
                        'is_read' => false,
                    ]);
                }
            }
        }
    }
}
