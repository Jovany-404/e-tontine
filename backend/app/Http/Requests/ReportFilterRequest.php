<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mois' => ['required', 'integer', 'min:1', 'max:12'],
            'année' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function messages(): array
    {
        return [
            'mois.required' => 'Le mois est requis',
            'mois.min' => 'Le mois doit être entre 1 et 12',
            'mois.max' => 'Le mois doit être entre 1 et 12',
            'année.required' => 'L\'année est requise',
        ];
    }
}
