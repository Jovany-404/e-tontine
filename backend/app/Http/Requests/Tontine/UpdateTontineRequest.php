<?php

namespace App\Http\Requests\Tontine;

use App\Enums\Frequency;
use App\Enums\TontineType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTontineRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation passe AVANT la validation : un étranger reçoit 404 même avec des données invalides.
        Gate::authorize('update', $this->route('tontine'));

        return true;
    }

    /**
     * "sometimes" : seuls les champs envoyés sont validés et modifiés.
     * Les règles qui dépendent de l'état de la tontine (champs verrouillés, date passée,
     * nombre de membres) sont dans TontineService.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', Rule::enum(TontineType::class)],
            'contribution_amount' => ['sometimes', 'required', 'integer', 'min:500', 'max:10000000'],
            'frequency' => ['sometimes', 'required', Rule::enum(Frequency::class)],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'expected_members' => ['sometimes', 'required', 'integer', 'min:2', 'max:100'],
            'rules' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
