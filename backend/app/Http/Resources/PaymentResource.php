<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'contribution_id' => $this->contribution_id,
            'reference' => $this->reference,
            'methode' => $this->methode,
            'organisme' => $this->organisme,
            'montant' => $this->montant,
            'date_paiement' => $this->date_paiement->format('Y-m-d'),
            'chemin_piece_jointe' => $this->chemin_piece_jointe,
            'commentaire' => $this->commentaire,
            'validated_by' => $this->validated_by,
            'date_validation' => $this->date_validation ? $this->date_validation->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
