<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTontineRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'montant_total' => ['sometimes', 'numeric', 'min:0'],
            'montant_membre' => ['sometimes', 'numeric', 'min:0'],
            'nombre_membres' => ['sometimes', 'integer', 'min:1'],
            'nombre_cycles' => ['sometimes', 'integer', 'min:1'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['nullable', 'date'],
            'type_rotation' => ['sometimes', 'in:chrono,aleatoire,custom'],
            'delai_paiement_jours' => ['sometimes', 'integer', 'min:0'],
            'notification_active' => ['sometimes', 'boolean'],
        ];
    }
}
