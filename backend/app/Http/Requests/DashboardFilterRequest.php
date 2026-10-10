<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'statut' => ['nullable', 'in:active,archivee,close,en_attente'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ];
    }
}
