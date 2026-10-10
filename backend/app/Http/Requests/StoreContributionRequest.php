<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'min:0'],
            'date_paiement' => ['required', 'date'],
            'date_echeance' => ['required', 'date', 'after:date_paiement'],
            'statut' => ['nullable', 'in:en_attente,paye,retard,annule'],
            'commentaire' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.required' => 'Le montant est requis',
            'date_paiement.required' => 'La date de paiement est requise',
            'date_echeance.required' => 'La date d\'échéance est requise',
            'date_echeance.after' => 'La date d\'échéance doit être après la date de paiement',
        ];
    }
}
