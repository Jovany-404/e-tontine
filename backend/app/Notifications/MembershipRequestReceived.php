<?php

namespace App\Notifications;

use App\Models\MembershipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MembershipRequestReceived extends Notification
{
    use Queueable;

    public function __construct(private MembershipRequest $membershipRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => 'membership_request.received',
            'membership_request_id' => $this->membershipRequest->public_id,
            'tontine_id' => $this->membershipRequest->tontine->public_id,
        ];
    }
}
