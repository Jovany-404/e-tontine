<?php

namespace App\Http\Resources;

use App\Models\ContributionSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContributionSchedule
 */
class ContributionScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'cycle_number' => $this->whenLoaded('cycle', fn () => $this->cycle->number),
            'round' => $this->round,
            'due_date' => $this->due_date->toDateString(),
            'amount_expected' => $this->amount_expected,
            'amount_paid' => $this->amount_paid,
            'remaining_amount' => $this->remainingAmount(),
            'status' => $this->status->value,
            'is_overdue' => $this->isOverdue(),
            'days_late' => $this->daysLate(),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->user->public_id,
                'name' => $this->member->user->name,
            ]),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
