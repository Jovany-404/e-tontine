<?php

namespace Tests\Feature;

use App\Enums\CycleStatus;
use App\Enums\MemberStatus;
use App\Enums\TontineStatus;
use App\Models\ActivityLog;
use App\Models\ContributionSchedule;
use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CycleTest extends TestCase
{
    use RefreshDatabase;

    private function tontine(array $attributes = []): Tontine
    {
        return Tontine::factory()->create($attributes);
    }

    private function managerOf(Tontine $tontine): User
    {
        return User::findOrFail($tontine->manager_id);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function url(Tontine $tontine): string
    {
        return '/api/v1/tontines/'.$tontine->public_id.'/cycles/start';
    }

    private function addMember(Tontine $tontine, array $attributes = []): User
    {
        $user = User::factory()->create();

        TontineMember::factory()->create(array_merge([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ], $attributes));

        return $user;
    }

    public function test_manager_starts_first_cycle_and_schedules_every_active_member_for_every_round(): void
    {
        $tontine = $this->tontine([
            'frequency' => 'weekly',
            'start_date' => '2026-11-02',
            'contribution_amount' => 15000,
            'expected_members' => 6,
        ]);
        $firstMember = $this->addMember($tontine, ['joined_at' => '2026-10-01 10:00:00']);
        $secondMember = $this->addMember($tontine, ['joined_at' => '2026-10-02 10:00:00']);
        $inactiveMember = $this->addMember($tontine, ['status' => MemberStatus::Inactive]);
        $manager = $this->managerOf($tontine);

        $response = $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine), [
                'status' => 'completed',
                'manager_id' => $firstMember->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.number', 1)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.members_count', 3)
            ->assertJsonPath('data.schedules_count', 9)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.tontine_id');

        $cycle = Cycle::query()->where('tontine_id', $tontine->id)->firstOrFail();
        $this->assertSame(CycleStatus::Open, $cycle->status);
        $this->assertNotNull($cycle->started_at);
        $this->assertSame(TontineStatus::Active, $tontine->fresh()->status);
        $this->assertDatabaseCount('cycles', 1);
        $this->assertDatabaseCount('contribution_schedules', 9);
        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $manager->id,
            'action' => 'cycle.started',
            'subject_type' => 'cycle',
            'subject_id' => $cycle->id,
        ]);

        $roundOne = ContributionSchedule::query()
            ->where('cycle_id', $cycle->id)
            ->where('round', 1)
            ->orderBy('tontine_member_id')
            ->get();

        $this->assertSame(
            [
                $tontine->members()->orderBy('id')->value('id'),
                $tontine->members()->where('user_id', $firstMember->id)->value('id'),
                $tontine->members()->where('user_id', $secondMember->id)->value('id'),
            ],
            $roundOne->pluck('tontine_member_id')->all(),
        );

        foreach ($roundOne as $schedule) {
            $this->assertSame('2026-11-02', $schedule->due_date->toDateString());
            $this->assertSame(15000, $schedule->amount_expected);
            $this->assertSame(0, $schedule->amount_paid);
            $this->assertSame('pending', $schedule->status->value);
        }

        $this->assertDatabaseMissing('contribution_schedules', [
            'cycle_id' => $cycle->id,
            'tontine_member_id' => $tontine->members()->where('user_id', $inactiveMember->id)->value('id'),
        ]);

        $this->assertSame(3, $response->json('data.members_count'));
    }

    public function test_daily_and_weekly_frequencies_advance_the_due_date_per_round(): void
    {
        foreach ([
            ['frequency' => 'daily', 'expected_dates' => ['2026-11-02', '2026-11-03']],
            ['frequency' => 'weekly', 'expected_dates' => ['2026-11-02', '2026-11-09']],
        ] as $case) {
            $tontine = $this->tontine([
                'frequency' => $case['frequency'],
                'start_date' => '2026-11-02',
                'expected_members' => 2,
            ]);
            $this->addMember($tontine);

            $this->withToken($this->tokenFor($this->managerOf($tontine)))
                ->postJson($this->url($tontine))
                ->assertCreated();

            $cycle = Cycle::query()->where('tontine_id', $tontine->id)->firstOrFail();
            $dates = ContributionSchedule::query()
                ->where('cycle_id', $cycle->id)
                ->where('tontine_member_id', $tontine->members()->firstOrFail()->id)
                ->orderBy('round')
                ->pluck('due_date')
                ->map(fn ($date) => $date->toDateString())
                ->all();

            $this->assertSame($case['expected_dates'], $dates);
        }
    }

    public function test_monthly_schedule_does_not_overflow_at_the_end_of_a_month(): void
    {
        $tontine = $this->tontine([
            'frequency' => 'monthly',
            'start_date' => '2027-01-31',
            'expected_members' => 2,
        ]);
        $this->addMember($tontine);

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->postJson($this->url($tontine))
            ->assertCreated();

        $cycle = Cycle::query()->where('tontine_id', $tontine->id)->firstOrFail();
        $dates = ContributionSchedule::query()
            ->where('cycle_id', $cycle->id)
            ->where('tontine_member_id', $tontine->members()->firstOrFail()->id)
            ->orderBy('round')
            ->pluck('due_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertSame(['2027-01-31', '2027-02-28'], $dates);
    }

    public function test_start_requires_at_least_two_active_members(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('members');

        $this->assertSame(TontineStatus::Draft, $tontine->fresh()->status);
        $this->assertDatabaseCount('cycles', 0);
        $this->assertDatabaseCount('contribution_schedules', 0);
    }

    public function test_failed_cycle_start_is_atomic_and_does_not_log_activity(): void
    {
        $tontine = $this->tontine(['expected_members' => 2]);
        $manager = $this->managerOf($tontine);
        $this->addMember($tontine);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->assertCreated();

        // Le changement d'état rend le second démarrage invalide.
        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tontine');

        $this->assertDatabaseCount('cycles', 1);
        $this->assertDatabaseCount('contribution_schedules', 4);
        $this->assertSame(1, ActivityLog::where('action', 'cycle.started')->count());
    }

    public function test_cycle_cannot_start_when_a_cycle_already_exists(): void
    {
        $tontine = $this->tontine();
        $this->addMember($tontine);
        Cycle::factory()->create(['tontine_id' => $tontine->id]);

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->postJson($this->url($tontine))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cycle');

        $this->assertSame(TontineStatus::Draft, $tontine->fresh()->status);
        $this->assertDatabaseCount('contribution_schedules', 0);
    }

    public function test_only_a_draft_tontine_can_start_its_first_cycle(): void
    {
        foreach ([TontineStatus::Active, TontineStatus::Completed] as $status) {
            $tontine = $this->tontine(['status' => $status]);
            $this->addMember($tontine);

            $this->withToken($this->tokenFor($this->managerOf($tontine)))
                ->postJson($this->url($tontine))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('tontine');
        }

        $this->assertDatabaseCount('cycles', 0);
    }

    public function test_start_requires_authentication_and_verified_email(): void
    {
        $tontine = $this->tontine();
        $this->addMember($tontine);

        $this->postJson($this->url($tontine))->assertUnauthorized();

        $manager = $this->managerOf($tontine);
        $manager->forceFill(['email_verified_at' => null])->save();

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');

        $this->assertDatabaseCount('cycles', 0);
    }

    public function test_only_the_manager_can_start_a_cycle(): void
    {
        $tontine = $this->tontine();
        $member = $this->addMember($tontine);

        $this->withToken($this->tokenFor($member))
            ->postJson($this->url($tontine))
            ->assertForbidden();

        $this->assertDatabaseCount('cycles', 0);
    }

    public function test_outsider_and_foreign_manager_get_404(): void
    {
        $tontine = $this->tontine();
        $this->addMember($tontine);
        $outsider = User::factory()->create();
        $foreignManager = $this->managerOf($this->tontine());

        foreach ([$outsider, $foreignManager] as $user) {
            $this->withToken($this->tokenFor($user))
                ->postJson($this->url($tontine), ['invalid' => true])
                ->assertNotFound()
                ->assertExactJson(['message' => 'Not Found.']);
        }

        $this->assertDatabaseCount('cycles', 0);
    }

    public function test_start_endpoint_is_rate_limited(): void
    {
        $tontine = $this->tontine();
        $token = $this->tokenFor($this->managerOf($tontine));

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withToken($token)
                ->postJson($this->url($tontine))
                ->assertUnprocessable();
        }

        $this->withToken($token)
            ->postJson($this->url($tontine))
            ->assertStatus(429);
    }
}
