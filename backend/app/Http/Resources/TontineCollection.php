<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TontineCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($tontine) {
                return [
                    'id' => $tontine->id,
                    'nom' => $tontine->nom,
                    'statut' => $tontine->statut,
                    'montant_total' => $tontine->montant_total,
                    'montant_membre' => $tontine->montant_membre,
                    'nombre_membres' => $tontine->nombre_membres,
                    'progression' => $tontine->progression,
                    'date_debut' => $tontine->date_debut->format('Y-m-d'),
                    'created_at' => $tontine->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
