<?php

namespace App\Notifications;

use Illuminate\Notification\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification
{
    protected $membership;
    protected $contribution;

    public function __construct($membership, $contribution)
    {
        $this->membership = $membership;
        $this->contribution = $contribution;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Rappel de paiement - Tontine')
            ->line('Bonjour ' . $this->membership->member->nom_complet)
            ->line('Votre contribution de ' . $this->contribution->montant . ' FCU est attendue.')
            ->line('Date d\'échéance: ' . $this->contribution->date_echeance->format('d/m/Y'))
            ->line('Montant restant: ' . $this->membership->montant_restant . ' FCU')
            ->action('Voir ma tontine', url('/'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'membership_id' => $this->membership->id,
            'contribution_id' => $this->contribution->id,
            'titre' => 'Rappel de paiement',
            'message' => 'Votre contribution de ' . $this->contribution->montant . ' FCU est attendue pour le ' . $this->contribution->date_echeance->format('d/m/Y'),
        ];
    }
}
