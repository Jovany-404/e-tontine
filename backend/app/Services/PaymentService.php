<?php

namespace App\Services;

use App\Enums\ScheduleStatus;
use App\Models\ContributionSchedule;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function record(
        Tontine $tontine,
        ContributionSchedule $schedule,
        User $manager,
        array $attributes,
    ): Payment {
        return DB::transaction(function () use ($tontine, $schedule, $manager, $attributes): Payment {
            $lockedSchedule = ContributionSchedule::query()
                ->whereKey($schedule->getKey())
                ->whereHas('cycle', fn ($query) => $query->where('tontine_id', $tontine->id))
                ->lockForUpdate()
                ->firstOrFail();

            $amountAlreadyPaid = (int) $lockedSchedule->payments()->sum('amount');
            $remainingAmount = $lockedSchedule->amount_expected - $amountAlreadyPaid;

            if ($remainingAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => [__('This contribution schedule has already been fully paid.')],
                ]);
            }

            if ($attributes['amount'] > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => [__('The payment amount exceeds the remaining amount due.')],
                ]);
            }

            $payment = $lockedSchedule->payments()->make([
                'amount' => $attributes['amount'],
                'method' => $attributes['method'],
                'reference' => $attributes['reference'] ?? null,
                'note' => $attributes['note'] ?? null,
                'paid_at' => $attributes['paid_at'] ?? today()->toDateString(),
            ]);
            $payment->recorded_by = $manager->id;
            $payment->save();

            $amountPaid = $amountAlreadyPaid + $payment->amount;
            $isPaid = $amountPaid === $lockedSchedule->amount_expected;

            $lockedSchedule->amount_paid = $amountPaid;
            $lockedSchedule->status = $isPaid ? ScheduleStatus::Paid : ScheduleStatus::Partial;
            $lockedSchedule->paid_at = $isPaid
                ? $payment->paid_at->toDateString()
                : null;
            $lockedSchedule->save();

            $this->activityLogger->log('payment.recorded', $manager, $tontine, $payment, [
                'schedule_id' => $lockedSchedule->public_id,
                'amount' => $payment->amount,
                'method' => $payment->method->value,
            ]);

            return $payment->load([
                'schedule.member.user:id,public_id,name',
                'schedule.cycle.tontine:id,public_id,name',
            ]);
        });
    }

    public function updateExpectedAmount(
        Tontine $tontine,
        ContributionSchedule $schedule,
        User $manager,
        int $amountExpected,
    ): ContributionSchedule {
        return DB::transaction(function () use ($tontine, $schedule, $manager, $amountExpected): ContributionSchedule {
            $lockedSchedule = ContributionSchedule::query()
                ->whereKey($schedule->getKey())
                ->whereHas('cycle', fn ($query) => $query->where('tontine_id', $tontine->id))
                ->lockForUpdate()
                ->firstOrFail();
            $amountAlreadyPaid = (int) $lockedSchedule->payments()->sum('amount');

            if ($amountExpected < $amountAlreadyPaid) {
                throw ValidationException::withMessages([
                    'amount_expected' => [__('The expected amount cannot be less than the amount already paid.')],
                ]);
            }

            $previousAmount = $lockedSchedule->amount_expected;
            $lockedSchedule->amount_expected = $amountExpected;
            $lockedSchedule->amount_paid = $amountAlreadyPaid;
            $lockedSchedule->status = match (true) {
                $amountAlreadyPaid === 0 => ScheduleStatus::Pending,
                $amountAlreadyPaid === $amountExpected => ScheduleStatus::Paid,
                default => ScheduleStatus::Partial,
            };
            $lockedSchedule->paid_at = $lockedSchedule->status === ScheduleStatus::Paid
                ? $lockedSchedule->payments()->max('paid_at')
                : null;
            $lockedSchedule->save();

            $this->activityLogger->log('schedule.amount_updated', $manager, $tontine, $lockedSchedule, [
                'previous_amount_expected' => $previousAmount,
                'amount_expected' => $amountExpected,
            ]);

            return $lockedSchedule->load([
                'cycle:id,tontine_id,number',
                'member.user:id,public_id,name',
                'payments',
            ]);
        });
    }
}
