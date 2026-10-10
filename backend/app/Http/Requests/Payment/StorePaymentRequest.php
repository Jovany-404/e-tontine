<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Models\ContributionSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tontine = $this->route('tontine');
        $schedule = $this->route('schedule');

        Gate::authorize('recordPayments', $tontine);

        // Refuse de révéler ou d'accepter une échéance appartenant à une autre tontine.
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
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'paid_at' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'montant encaissé',
            'method' => 'mode de paiement',
            'reference' => 'référence',
            'note' => 'note',
            'paid_at' => 'date de paiement',
        ];
    }
}
