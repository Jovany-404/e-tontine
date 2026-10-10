<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'position' => ['sometimes', 'integer', 'min:1'],
            'montant_engage' => ['sometimes', 'numeric', 'min:0'],
            'statut' => ['sometimes', 'in:actif,retard,inactif,exclu'],
        ];
    }
}
