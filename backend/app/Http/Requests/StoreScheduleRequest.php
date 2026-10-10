<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tontine_id' => ['required', 'integer', 'exists:tontines,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'position_beneficiaire' => ['required', 'integer', 'min:1'],
            'date_benefice' => ['required', 'date'],
            'montant_benefice' => ['required', 'numeric', 'min:0'],
            'statut' => ['nullable', 'in:prevu,realise,annule'],
            'observations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tontine_id.required' => 'La tontine est requise',
            'tontine_id.exists' => 'La tontine sélectionnée n\'existe pas',
            'member_id.required' => 'Le membre est requis',
            'member_id.exists' => 'Le membre sélectionné n\'existe pas',
            'position_beneficiaire.required' => 'La position bénéficiaire est requise',
            'date_benefice.required' => 'La date de bénéfice est requise',
            'montant_benefice.required' => 'Le montant de bénéfice est requis',
        ];
    }
}
