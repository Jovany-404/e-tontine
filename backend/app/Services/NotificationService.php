<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Membership;
use App\Models\Tontine;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    public function create(array $data): Notification
    {
        $notification = Notification::create([
            'user_id' => $data['user_id'] ?? Auth::id(),
            'membership_id' => $data['membership_id'] ?? null,
            'tontine_id' => $data['tontine_id'] ?? null,
            'contribution_id' => $data['contribution_id'] ?? null,
            'type' => $data['type'],
            'titre' => $data['titre'],
            'message' => $data['message'],
            'is_read' => false,
        ]);

        return $notification;
    }

    public function sendPaymentReminder(Membership $membership): void
    {
        $titre = 'Rappel de paiement';
        $message = sprintf(
            'Bonjour %s, votre contribution de %s FCU est attendue pour le %s. Montant restant: %s FCU',
            $membership->member->nom,
            $membership->montant_engage,
            $membership->contributions()->where('statut', 'en_attente')->first()->date_echeance,
            $membership->montant_restant
        );

        Notification::create([
            'user_id' => Auth::id(),
            'membership_id' => $membership->id,
            'tontine_id' => $membership->tontine_id,
            'type' => 'reminder',
            'titre' => $titre,
            'message' => $message,
        ]);
    }

    public function sendLateContribution(Membership $membership, int $joursDelai): void
    {
        $titre = 'Contribution en retard';
        $message = sprintf(
            'Attention: Votre contribution est en retard de %d jours. Montant: %s FCU',
            $joursDelai,
            $membership->montant_restant
        );

        Notification::create([
            'user_id' => Auth::id(),
            'membership_id' => $membership->id,
            'tontine_id' => $membership->tontine_id,
            'type' => 'alert',
            'titre' => $titre,
            'message' => $message,
        ]);
    }

    public function getByUser(int $userId, bool $nonLis = false): array
    {
        $query = Notification::query()->where('user_id', $userId);

        if ($nonLis) {
            $query->where('is_read', false);
        }

        $notifications = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return [
            'notifications' => $notifications->toArray(),
            'count_total' => $notifications->count(),
            'count_nonLis' => $notifications->where('is_read', false)->count(),
        ];
    }

    public function markAsRead(int $notificationId): void
    {
        $notification = Notification::find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
