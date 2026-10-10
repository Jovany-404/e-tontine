<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ScheduleCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'position_beneficiaire' => $schedule->position_beneficiaire,
                    'member_nom' => $schedule->member ? $schedule->member->nom_complet : null,
                    'date_benefice' => $schedule->date_benefice->format('Y-m-d'),
                    'montant_benefice' => $schedule->montant_benefice,
                    'statut' => $schedule->statut,
                ];
            }),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
