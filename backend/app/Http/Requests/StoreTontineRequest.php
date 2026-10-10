<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTontineRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'montant_total' => ['required', 'numeric', 'min:0'],
            'montant_membre' => ['required', 'numeric', 'min:0'],
            'nombre_membres' => ['required', 'integer', 'min:1'],
            'nombre_cycles' => ['required', 'integer', 'min:1'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'type_rotation' => ['nullable', 'in:chrono,aleatoire,custom'],
            'delai_paiement_jours' => ['nullable', 'integer', 'min:0'],
            'notification_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la tontine est requis',
            'montant_total.required' => 'Le montant total est requis',
            'montant_membre.required' => 'Le montant par membre est requis',
            'nombre_membres.required' => 'Le nombre de membres est requis',
            'nombre_cycles.required' => 'Le nombre de cycles est requis',
            'date_debut.required' => 'La date de début est requise',
            'date_fin.after' => 'La date de fin doit être après la date de début',
        ];
    }
}
