<?php

namespace App\Services;

use App\Enums\Frequency;
use App\Enums\MemberStatus;
use App\Enums\TontineStatus;
use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CycleService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * Démarre le premier cycle et crée une échéance pour chaque membre actif à chaque tour.
     *
     * @return array{cycle: Cycle, members_count: int, schedules_count: int}
     */
    public function startFirst(Tontine $tontine, User $manager): array
    {
        return DB::transaction(function () use ($tontine, $manager): array {
            $tontine = Tontine::query()
                ->whereKey($tontine->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($tontine->status !== TontineStatus::Draft) {
                throw ValidationException::withMessages([
                    'tontine' => [__('Only a draft tontine can start its first cycle.')],
                ]);
            }

            if ($tontine->cycles()->exists()) {
                throw ValidationException::withMessages([
                    'cycle' => [__('A cycle has already been created for this tontine.')],
                ]);
            }

            $members = $tontine->members()
                ->where('status', MemberStatus::Active->value)
                ->orderBy('joined_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'tontine_id']);

            $membersCount = $members->count();

            if ($membersCount < 2) {
                throw ValidationException::withMessages([
                    'members' => [__('At least two active members are required to start a tontine.')],
                ]);
            }

            $cycle = $tontine->cycles()->create([
                'number' => 1,
                'started_at' => now(),
            ]);

            $firstDueDate = CarbonImmutable::parse($tontine->start_date->toDateString());
            $schedules = [];

            for ($round = 1; $round <= $membersCount; $round++) {
                $dueDate = $this->dueDateForRound($firstDueDate, $tontine->frequency, $round - 1);

                foreach ($members as $member) {
                    $schedules[] = [
                        'public_id' => (string) Str::ulid(),
                        'cycle_id' => $cycle->id,
                        'round' => $round,
                        'tontine_member_id' => $member->id,
                        'due_date' => $dueDate->toDateString(),
                        'amount_expected' => $tontine->contribution_amount,
                        'amount_paid' => 0,
                        'status' => 'pending',
                        'paid_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            $cycle->schedules()->insert($schedules);
            $tontine->status = TontineStatus::Active;
            $tontine->save();

            $this->activityLogger->log('cycle.started', $manager, $tontine, $cycle, [
                'number' => $cycle->number,
                'members_count' => $membersCount,
                'schedules_count' => count($schedules),
            ]);

            return [
                'cycle' => $cycle,
                'members_count' => $membersCount,
                'schedules_count' => count($schedules),
            ];
        });
    }

    private function dueDateForRound(
        CarbonImmutable $firstDueDate,
        Frequency $frequency,
        int $roundOffset,
    ): CarbonImmutable {
        return match ($frequency) {
            Frequency::Daily => $firstDueDate->addDays($roundOffset),
            Frequency::Weekly => $firstDueDate->addWeeks($roundOffset),
            // Évite de déborder au mois suivant lorsque la date tombe en fin de mois.
            Frequency::Monthly => $firstDueDate->addMonthsNoOverflow($roundOffset),
        };
    }
}
