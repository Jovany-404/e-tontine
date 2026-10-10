<?php

namespace App\Http\Requests\Tontine;

use App\Enums\Frequency;
use App\Enums\TontineType;
use App\Models\Tontine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTontineRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TontinePolicy::create : réservé aux emails vérifiés.
        Gate::authorize('create', Tontine::class);

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', Rule::enum(TontineType::class)],
            // Montant en FCFA, entier.
            'contribution_amount' => ['required', 'integer', 'min:500', 'max:10000000'],
            'frequency' => ['required', Rule::enum(Frequency::class)],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'expected_members' => ['required', 'integer', 'min:2', 'max:100'],
            'rules' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
