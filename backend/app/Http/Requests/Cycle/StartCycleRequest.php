<?php

namespace App\Http\Requests\Cycle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StartCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le gérant est autorisé avant toute validation métier.
        Gate::authorize('startCycle', $this->route('tontine'));

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
