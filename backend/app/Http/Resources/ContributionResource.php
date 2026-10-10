<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContributionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'membership_id' => $this->membership_id,
            'tontine_id' => $this->tontine_id,
            'montant' => $this->montant,
            'montant_initial' => $this->montant_initial,
            'date_paiement' => $this->date_paiement ? $this->date_paiement->format('Y-m-d') : null,
            'date_echeance' => $this->date_echeance ? $this->date_echeance->format('Y-m-d') : null,
            'statut' => $this->statut,
            'jours_delai' => $this->jours_delai,
            'commentaire' => $this->commentaire,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
