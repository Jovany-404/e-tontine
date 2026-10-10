<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MembershipCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($membership) {
                return [
                    'id' => $membership->id,
                    'member_nom' => $membership->member ? $membership->member->nom_complet : null,
                    'montant_engage' => $membership->montant_engage,
                    'montant_paye' => $membership->montant_paye,
                    'montant_restant' => $membership->montant_restant,
                    'statut' => $membership->statut,
                    'progression' => $membership->progression,
                ];
            }),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
