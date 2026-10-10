<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tontine_id' => $this->tontine_id,
            'member_id' => $this->member_id,
            'membership_id' => $this->membership_id,
            'action' => $this->action,
            'entite' => $this->entite,
            'entite_id' => $this->entite_id,
            'description' => $this->description,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
