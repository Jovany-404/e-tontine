<?php

namespace App\Http\Resources;

use App\Enums\MemberRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tontine
 */
class TontineResource extends JsonResource
{
    // Rôle de l'utilisateur connecté DANS cette tontine (calculé par le serveur, jamais par le front).
    public function __construct($resource, private ?MemberRole $myRole = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'contribution_amount' => $this->contribution_amount,
            'frequency' => $this->frequency->value,
            'start_date' => $this->start_date->toDateString(),
            'expected_members' => $this->expected_members,
            'rules' => $this->rules,
            'status' => $this->status->value,
            // Présent uniquement si le contrôleur a chargé le comptage.
            'members_count' => $this->whenCounted('activeMembers'),
            'my_role' => $this->myRole?->value,
            // Minimisation : seulement le nom du gérant, jamais son email ni son téléphone.
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager->public_id,
                'name' => $this->manager->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
