<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation passe AVANT la validation : un étranger reçoit 404 même avec des données invalides.
        Gate::authorize('manageInvitations', $this->route('tontine'));

        return true;
    }

    public function rules(): array
    {
        return [
            // Absent : 7 jours. null : aucune expiration. Sinon de 1 à 90 jours.
            'expires_in_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:90'],
        ];
    }

    public function attributes(): array
    {
        return [
            'expires_in_days' => "durée de validité",
        ];
    }
}
