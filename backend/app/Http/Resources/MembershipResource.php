<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tontine_id' => $this->tontine_id,
            'member_id' => $this->member_id,
            'position' => $this->position,
            'montant_engage' => $this->montant_engage,
            'montant_paye' => $this->montant_paye,
            'montant_restant' => $this->montant_restant,
            'statut' => $this->statut,
            'date_inscription' => $this->date_inscription->format('Y-m-d'),
            'date_dernier_paiement' => $this->date_dernier_paiement ? $this->date_dernier_paiement->format('Y-m-d') : null,
            'progression' => $this->progression,
            'member' => $this->member ? [
                'id' => $this->member->id,
                'nom' => $this->member->nom,
                'prenom' => $this->member->prenom,
                'nom_complet' => $this->member->nom_complet,
                'telephone' => $this->member->telephone,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
