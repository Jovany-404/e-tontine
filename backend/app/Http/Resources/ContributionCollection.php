<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ContributionCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($contribution) {
                return [
                    'id' => $contribution->id,
                    'montant' => $contribution->montant,
                    'date_paiement' => $contribution->date_paiement ? $contribution->date_paiement->format('Y-m-d') : null,
                    'statut' => $contribution->statut,
                    'jours_delai' => $contribution->jours_delai,
                ];
            }),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
