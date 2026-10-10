<?php

namespace App\Notifications;

use Illuminate\Notification\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateContributionNotification extends Notification
{
    protected $membership;
    protected $joursDelai;

    public function __construct($membership, int $joursDelai)
    {
        $this->membership = $membership;
        $this->joursDelai = $joursDelai;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contribution en retard - Tontine')
            ->line('Attention: Votre contribution est en retard de ' . $this->joursDelai . ' jours.')
            ->line('Montant restant: ' . $this->membership->montant_restant . ' FCU')
            ->line('Veuillez régulariser votre situation rapidement.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'membership_id' => $this->membership->id,
            'titre' => 'Contribution en retard',
            'message' => 'Votre contribution est en retard de ' . $this->joursDelai . ' jours. Montant restant: ' . $this->membership->montant_restant . ' FCU',
        ];
    }
}
