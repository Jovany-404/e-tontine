<?php

namespace App\Http\Requests\Membership;

use App\Models\Invitation;
use App\Models\Tontine;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tontine = $this->route('tontine');
        $token = $this->input('invitation_token');

        // Un lien absent, invalide ou lié à une autre tontine reçoit le même 404, avant validation.
        if (! $tontine instanceof Tontine || ! is_string($token)
            || ! Invitation::query()
                ->usable()
                ->where('tontine_id', $tontine->id)
                ->where('token_hash', Invitation::hashToken($token))
                ->exists()) {
            abort(404);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'invitation_token' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'invitation_token' => "lien d'invitation",
        ];
    }
}
