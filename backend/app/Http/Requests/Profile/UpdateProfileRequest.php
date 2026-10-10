<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Seuls ces champs sont modifiables. L'email, les statuts de vérification
     * et le statut d'identité n'apparaissent pas ici : ils sont ignorés s'ils sont envoyés.
     * "sometimes" permet d'envoyer uniquement les champs à changer.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[0-9 .\-]{8,20}$/'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }
}
