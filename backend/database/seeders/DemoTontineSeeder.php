<?php

namespace Database\Seeders;

use App\Enums\CycleStatus;
use App\Enums\Frequency;
use App\Enums\IdentityStatus;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\ScheduleStatus;
use App\Enums\TontineStatus;
use App\Enums\TontineType;
use App\Models\ContributionSchedule;
use App\Models\Cycle;
use App\Models\Draw;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoTontineSeeder extends Seeder
{
    private const TONTINE_NAME = 'Tontine Demo Teranga';

    public function run(): void
    {
        $password = (string) env('DEMO_TONTINE_PASSWORD');

        if (strlen($password) < 12) {
            throw new RuntimeException(
                'Set DEMO_TONTINE_PASSWORD to a password of at least 12 characters before running this seeder.',
            );
        }

        DB::transaction(function () use ($password): void {
            $people = [
                ['name' => 'Awa Ndiaye', 'email' => 'awa.demo@etontine.test'],
                ['name' => 'Mamadou Diop', 'email' => 'mamadou.demo@etontine.test'],
                ['name' => 'Fatou Sow', 'email' => 'fatou.demo@etontine.test'],
                ['name' => 'Ibrahima Ba', 'email' => 'ibrahima.demo@etontine.test'],
                ['name' => 'Khady Fall', 'email' => 'khady.demo@etontine.test'],
            ];

            $users = collect($people)->map(function (array $person) use ($password): User {
                $user = User::query()->firstOrNew(['email' => $person['email']]);
                $user->fill([
                    'name' => $person['name'],
                    'city' => 'Dakar',
                    'password' => Hash::make($password),
                ]);
                $user->forceFill([
                    'email_verified_at' => now(),
                    'identity_status' => IdentityStatus::Unverified,
                ])->save();

                return $user;
            });

            /** @var User $manager */
            $manager = $users->first();

            $tontine = Tontine::query()
                ->where('manager_id', $manager->id)
                ->where('name', self::TONTINE_NAME)
                ->first() ?? new Tontine;
            $tontine->forceFill(['manager_id' => $manager->id]);
            $tontine->fill([
                'name' => self::TONTINE_NAME,
                'description' => 'Données fictives de démonstration — aucun paiement ni tirage réel.',
                'type' => TontineType::Friends,
                'contribution_amount' => 10000,
                'frequency' => Frequency::Monthly,
                'start_date' => today()->subMonthsNoOverflow(8)->startOfMonth(),
                'expected_members' => $users->count(),
                'rules' => 'Démo uniquement : 10 000 FCFA par membre et par tour.',
            ]);
            $tontine->forceFill(['status' => TontineStatus::Active])->save();

            $members = $users->map(function (User $user, int $index) use ($tontine, $manager): TontineMember {
                return TontineMember::query()->updateOrCreate(
                    ['tontine_id' => $tontine->id, 'user_id' => $user->id],
                    [
                        'role' => $user->is($manager) ? MemberRole::Manager : MemberRole::Member,
                        'status' => MemberStatus::Active,
                        'joined_at' => today()->subMonthsNoOverflow(8)->addDays($index),
                    ],
                );
            })->values();

            $historyCycle = Cycle::query()
                ->where('tontine_id', $tontine->id)
                ->where('number', 1)
                ->first() ?? new Cycle;
            $historyCycle->forceFill([
                'tontine_id' => $tontine->id,
                'number' => 1,
            ]);
            $historyCycle->fill([
                'started_at' => today()->subMonthsNoOverflow(8)->startOfMonth(),
            ]);
            $historyCycle->forceFill([
                'status' => CycleStatus::Closed,
                'closed_at' => today()->subMonthsNoOverflow(3)->startOfMonth(),
            ])->save();

            $historyStart = CarbonImmutable::parse($historyCycle->started_at)->startOfDay();
            $historyWinners = [0, 1, 2, 3, 4];

            foreach ($historyWinners as $roundIndex => $winnerIndex) {
                $round = $roundIndex + 1;
                $dueDate = $historyStart->addMonthsNoOverflow($roundIndex);

                foreach ($members as $memberIndex => $member) {
                    $schedule = $this->schedule($historyCycle, $member, $round, $dueDate, 10000);
                    $paidAt = $dueDate->addDays(2);

                    $this->payment(
                        $schedule,
                        $manager,
                        10000,
                        "DEMO-C1-R{$round}-M".($memberIndex + 1),
                        $paidAt,
                    );

                    $schedule->forceFill([
                        'amount_paid' => 10000,
                        'status' => ScheduleStatus::Paid,
                        'paid_at' => $paidAt,
                    ])->save();
                }

                $this->draw(
                    $tontine,
                    $historyCycle,
                    $manager,
                    $members[$winnerIndex],
                    $round,
                    $members->count() * 10000,
                    $dueDate->addDays(3),
                );
            }

            $currentCycle = Cycle::query()
                ->where('tontine_id', $tontine->id)
                ->where('number', 2)
                ->first() ?? new Cycle;
            $currentCycle->forceFill([
                'tontine_id' => $tontine->id,
                'number' => 2,
            ]);
            $currentCycle->fill([
                'started_at' => today()->subMonthsNoOverflow(2)->startOfMonth(),
            ]);
            $currentCycle->forceFill([
                'status' => CycleStatus::Open,
                'closed_at' => null,
            ])->save();

            $currentStart = CarbonImmutable::parse($currentCycle->started_at)->startOfDay();

            for ($round = 1; $round <= $members->count(); $round++) {
                $dueDate = $currentStart->addMonthsNoOverflow($round - 1);

                foreach ($members as $memberIndex => $member) {
                    $schedule = $this->schedule($currentCycle, $member, $round, $dueDate, 10000);

                    if ($round === 1) {
                        $paidAt = $dueDate->addDay();
                        $this->payment(
                            $schedule,
                            $manager,
                            10000,
                            "DEMO-C2-R1-M".($memberIndex + 1),
                            $paidAt,
                        );
                        $schedule->forceFill([
                            'amount_paid' => 10000,
                            'status' => ScheduleStatus::Paid,
                            'paid_at' => $paidAt,
                        ])->save();
                    } elseif ($round === 2 && $memberIndex === 0) {
                        $paidAt = $dueDate->addDay();
                        $this->payment(
                            $schedule,
                            $manager,
                            5000,
                            'DEMO-C2-R2-M1-PARTIAL',
                            $paidAt,
                        );
                        $schedule->forceFill([
                            'amount_paid' => 5000,
                            'status' => ScheduleStatus::Partial,
                            'paid_at' => null,
                        ])->save();
                    }
                }
            }

            $this->draw(
                $tontine,
                $currentCycle,
                $manager,
                $members[0],
                1,
                $members->count() * 10000,
                $currentStart->addDays(4),
            );
        });

        $this->command?->info('Demo tontine seeded. All people, payments, and draws are fictional.');
    }

    private function schedule(
        Cycle $cycle,
        TontineMember $member,
        int $round,
        CarbonImmutable $dueDate,
        int $amount,
    ): ContributionSchedule {
        $schedule = ContributionSchedule::query()
            ->where('cycle_id', $cycle->id)
            ->where('round', $round)
            ->where('tontine_member_id', $member->id)
            ->first() ?? new ContributionSchedule;
        $schedule->forceFill([
            'cycle_id' => $cycle->id,
            'round' => $round,
            'tontine_member_id' => $member->id,
        ]);
        $schedule->fill([
                'due_date' => $dueDate->toDateString(),
                'amount_expected' => $amount,
            ]);
        $schedule->forceFill([
            'amount_paid' => 0,
            'status' => ScheduleStatus::Pending,
            'paid_at' => null,
        ])->save();

        return $schedule;
    }

    private function payment(
        ContributionSchedule $schedule,
        User $manager,
        int $amount,
        string $reference,
        CarbonImmutable $paidAt,
    ): void {
        $payment = Payment::query()
            ->where('schedule_id', $schedule->id)
            ->where('reference', $reference)
            ->first() ?? new Payment;
        $payment->forceFill(['schedule_id' => $schedule->id]);
        $payment->fill([
            'amount' => $amount,
            'method' => PaymentMethod::MobileMoney,
            'reference' => $reference,
            'note' => 'Paiement de démonstration fictif',
            'paid_at' => $paidAt->toDateString(),
        ]);
        $payment->recorded_by = $manager->id;
        $payment->save();
    }

    private function draw(
        Tontine $tontine,
        Cycle $cycle,
        User $manager,
        TontineMember $winner,
        int $round,
        int $amount,
        CarbonImmutable $drawnAt,
    ): void {
        $draw = Draw::query()
            ->where('cycle_id', $cycle->id)
            ->where('round', $round)
            ->first() ?? new Draw;
        $draw->forceFill(['cycle_id' => $cycle->id]);
        $draw->fill([
            'tontine_id' => $tontine->id,
            'round' => $round,
            'winner_member_id' => $winner->id,
            'amount' => $amount,
            'drawn_at' => $drawnAt,
        ]);
        $draw->drawn_by = $manager->id;
        $draw->save();
    }
}
