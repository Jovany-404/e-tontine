<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateContributionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date_paiement' => ['required', 'date'],
        ];
    }
}
