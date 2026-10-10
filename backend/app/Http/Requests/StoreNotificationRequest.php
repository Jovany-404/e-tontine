<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'membership_id' => ['nullable', 'integer', 'exists:memberships,id'],
            'tontine_id' => ['nullable', 'integer', 'exists:tontines,id'],
            'contribution_id' => ['nullable', 'integer', 'exists:contributions,id'],
            'type' => ['required', 'in:reminder,alert,confirmation,info,payment'],
            'titre' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'L\'utilisateur est requis',
            'type.required' => 'Le type de notification est requis',
            'titre.required' => 'Le titre est requis',
            'message.required' => 'Le message est requis',
        ];
    }
}
