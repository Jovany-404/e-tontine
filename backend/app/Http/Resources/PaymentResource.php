<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'amount' => $this->amount,
            'method' => $this->method->value,
            'reference' => $this->reference,
            'note' => $this->note,
            'paid_at' => $this->paid_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'schedule' => $this->whenLoaded('schedule', fn () => [
                'id' => $this->schedule->public_id,
                'round' => $this->schedule->round,
                'due_date' => $this->schedule->due_date->toDateString(),
                'member' => $this->schedule->member->user->name,
                'tontine' => [
                    'id' => $this->schedule->cycle->tontine->public_id,
                    'name' => $this->schedule->cycle->tontine->name,
                ],
            ]),
        ];
    }
}
