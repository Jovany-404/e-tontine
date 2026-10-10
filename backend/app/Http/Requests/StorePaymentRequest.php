<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:255', 'unique:payments'],
            'methode' => ['required', 'in:cash,transfert,mobile_money,bank,autre'],
            'organisme' => ['nullable', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_paiement' => ['required', 'date'],
            'chemin_piece_jointe' => ['nullable', 'string'],
            'commentaire' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'methode.required' => 'La méthode de paiement est requise',
            'montant.required' => 'Le montant est requis',
            'date_paiement.required' => 'La date de paiement est requise',
        ];
    }
}
