<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tontine_id' => $this->tontine_id,
            'position_beneficiaire' => $this->position_beneficiaire,
            'member_id' => $this->member_id,
            'date_benefice' => $this->date_benefice->format('Y-m-d'),
            'montant_benefice' => $this->montant_benefice,
            'statut' => $this->statut,
            'observations' => $this->observations,
            'member' => $this->member ? [
                'id' => $this->member->id,
                'nom' => $this->member->nom,
                'prenom' => $this->member->prenom,
                'nom_complet' => $this->member->nom_complet,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
