<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'membership_id' => $this->membership_id,
            'tontine_id' => $this->tontine_id,
            'contribution_id' => $this->contribution_id,
            'type' => $this->type,
            'titre' => $this->titre,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at ? $this->read_at->format('Y-m-d H:i:s') : null,
            'sent_at' => $this->sent_at ? $this->sent_at->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
