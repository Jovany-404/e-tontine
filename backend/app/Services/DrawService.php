<?php

namespace App\Services;

use App\Enums\CycleStatus;
use App\Enums\TontineStatus;
use App\Models\Cycle;
use App\Models\Draw;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DrawService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * Tire un bénéficiaire pour le premier tour non tiré après vérification de toutes les cotisations.
     *
     * @return array{draw: Draw, cycle_completed: bool}
     */
    public function drawNext(Tontine $tontine, User $manager): array
    {
        return DB::transaction(function () use ($tontine, $manager): array {
            $cycle = Cycle::query()
                ->where('tontine_id', $tontine->id)
                ->where('status', CycleStatus::Open->value)
                ->lockForUpdate()
                ->first();

            if ($cycle === null) {
                throw ValidationException::withMessages([
                    'cycle' => [__('There is no open cycle to draw.')],
                ]);
            }

            $drawnRounds = $cycle->draws()->pluck('round');
            $round = $cycle->schedules()
                ->whereNotIn('round', $drawnRounds)
                ->min('round');

            if ($round === null) {
                throw ValidationException::withMessages([
                    'round' => [__('All rounds in this cycle have already been drawn.')],
                ]);
            }

            $schedules = $cycle->schedules()
                ->where('round', $round)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'amount_expected', 'tontine_member_id']);

            if ($schedules->isEmpty()) {
                throw ValidationException::withMessages([
                    'round' => [__('This round has no contribution schedules.')],
                ]);
            }

            $scheduleIds = $schedules->pluck('id');
            $expectedAmount = (int) $schedules->sum('amount_expected');
            $receivedAmount = (int) Payment::query()
                ->whereIn('schedule_id', $scheduleIds)
                ->sum('amount');

            if ($receivedAmount < $expectedAmount) {
                throw ValidationException::withMessages([
                    'payments' => [__('All contributions for this round must be fully paid before the draw.')],
                ]);
            }

            $previousWinners = $cycle->draws()->pluck('winner_member_id');
            $eligibleMemberIds = $schedules->pluck('tontine_member_id')
                ->unique()
                ->reject(fn (int $memberId) => $previousWinners->contains($memberId))
                ->values();

            if ($eligibleMemberIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'members' => [__('There are no eligible members left in this cycle.')],
                ]);
            }

            $winnerMemberId = $eligibleMemberIds[random_int(0, $eligibleMemberIds->count() - 1)];
            $draw = $cycle->draws()->make([
                'tontine_id' => $tontine->id,
                'round' => $round,
                'winner_member_id' => $winnerMemberId,
                'amount' => $receivedAmount,
                'drawn_at' => now(),
            ]);
            $draw->drawn_by = $manager->id;
            $draw->save();

            $cycleCompleted = ! $cycle->schedules()
                ->whereNotIn('round', $cycle->draws()->pluck('round'))
                ->exists();

            if ($cycleCompleted) {
                $cycle->status = CycleStatus::Closed;
                $cycle->closed_at = now();
                $cycle->save();

                $tontine->status = TontineStatus::Completed;
                $tontine->save();
            }

            $this->activityLogger->log('draw.completed', $manager, $tontine, $draw, [
                'cycle_number' => $cycle->number,
                'round' => $draw->round,
                'amount' => $draw->amount,
            ]);

            return [
                'draw' => $draw->load([
                    'cycle:id,number',
                    'winner.user:id,public_id,name',
                ]),
                'cycle_completed' => $cycleCompleted,
            ];
        });
    }
}
