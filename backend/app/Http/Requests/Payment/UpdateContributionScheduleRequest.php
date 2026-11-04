<?php

namespace App\Http\Requests\Payment;

use App\Models\ContributionSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateContributionScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tontine = $this->route('tontine');
        $schedule = $this->route('schedule');

        Gate::authorize('recordPayments', $tontine);

        if (! $schedule instanceof ContributionSchedule
            || ! $schedule->cycle()
                ->where('tontine_id', $tontine->id)
                ->exists()) {
            abort(404);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'amount_expected' => ['required', 'integer', 'min:1', 'max:10000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount_expected' => 'montant à verser',
        ];
    }
}
