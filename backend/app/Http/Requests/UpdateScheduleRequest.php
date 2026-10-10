<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'position_beneficiaire' => ['sometimes', 'integer', 'min:1'],
            'date_benefice' => ['sometimes', 'date'],
            'montant_benefice' => ['sometimes', 'numeric', 'min:0'],
            'statut' => ['sometimes', 'in:prevu,realise,annule'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
