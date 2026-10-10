<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            // Même règle de complexité que l'inscription (Password::defaults), et différent de l'ancien.
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'password.different' => "Le nouveau mot de passe doit être différent de l'actuel.",
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'mot de passe actuel',
        ];
    }
}
