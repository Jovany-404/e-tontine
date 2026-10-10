<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TontineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'montant_total' => $this->montant_total,
            'montant_membre' => $this->montant_membre,
            'nombre_membres' => $this->nombre_membres,
            'nombre_cycles' => $this->nombre_cycles,
            'date_debut' => $this->date_debut->format('Y-m-d'),
            'date_fin' => $this->date_fin ? $this->date_fin->format('Y-m-d') : null,
            'statut' => $this->statut,
            'type_rotation' => $this->type_rotation,
            'delai_paiement_jours' => $this->delai_paiement_jours,
            'notification_active' => $this->notification_active,
            'montant_total_paye' => $this->montant_total_paye,
            'montant_total_restant' => $this->montant_total_restant,
            'nombre_membres_paye' => $this->nombre_membres_paye,
            'nombre_membres_retard' => $this->nombre_membres_retard,
            'progression' => $this->progression,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
