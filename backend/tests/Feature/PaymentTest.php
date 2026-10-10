<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\ScheduleStatus;
use App\Models\ContributionSchedule;
use App\Models\Cycle;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createSchedule(array $attributes = []): array
    {
        $tontine = Tontine::factory()->create();
        $member = User::factory()->create();
        $membership = TontineMember::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $member->id,
        ]);
        $cycle = Cycle::factory()->create(['tontine_id' => $tontine->id]);
        $schedule = ContributionSchedule::factory()->create(array_merge([
            'cycle_id' => $cycle->id,
            'tontine_member_id' => $membership->id,
            'amount_expected' => 10000,
            'due_date' => today()->subDay()->toDateString(),
        ], $attributes));

        return compact('tontine', 'member', 'membership', 'cycle', 'schedule');
    }

    private function managerFor(Tontine $tontine): User
    {
        return User::findOrFail($tontine->manager_id);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function paymentUrl(Tontine $tontine, ContributionSchedule $schedule): string
    {
        return '/api/v1/tontines/'.$tontine->public_id.'/schedules/'.$schedule->public_id.'/payments';
    }

    public function test_manager_can_record_partial_and_full_payments_transactionally(): void
    {
        ['tontine' => $tontine, 'schedule' => $schedule] = $this->createSchedule();
        $manager = $this->managerFor($tontine);
        $token = $this->tokenFor($manager);

        $firstResponse = $this->withToken($token)
            ->postJson($this->paymentUrl($tontine, $schedule), [
                'amount' => 4000,
                'method' => 'mobile_money',
                'recorded_by' => User::factory()->create()->id,
                'status' => 'paid',
            ])
            ->assertCreated()
            ->assertJsonPath('data.payment.amount', 4000)
            ->assertJsonPath('data.schedule.amount_paid', 4000)
            ->assertJsonPath('data.schedule.remaining_amount', 6000)
            ->assertJsonPath('data.schedule.status', 'partial')
            ->assertJsonMissingPath('data.payment.recorded_by')
            ->assertJsonMissingPath('data.payment.schedule_id')
            ->assertJsonMissingPath('data.schedule.tontine_id');

        $paymentId = $firstResponse->json('data.payment.id');
        $firstPayment = Payment::query()->where('public_id', $paymentId)->firstOrFail();
        $this->assertSame($manager->id, $firstPayment->recorded_by);
        $this->assertSame(ScheduleStatus::Partial, $schedule->fresh()->status);
        $this->assertNull($schedule->fresh()->paid_at);
        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $manager->id,
            'action' => 'payment.recorded',
            'subject_type' => 'payment',
            'subject_id' => $firstPayment->id,
        ]);

        $this->withToken($token)
            ->postJson($this->paymentUrl($tontine, $schedule), [
                'amount' => 6000,
                'method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.schedule.amount_paid', 10000)
            ->assertJsonPath('data.schedule.remaining_amount', 0)
            ->assertJsonPath('data.schedule.status', 'paid');

        $this->assertSame(ScheduleStatus::Paid, $schedule->fresh()->status);
        $this->assertSame(10000, $schedule->fresh()->amount_paid);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_payment_cannot_exceed_remaining_due_or_be_recorded_twice_after_full_payment(): void
    {
        ['tontine' => $tontine, 'schedule' => $schedule] = $this->createSchedule([
            'amount_paid' => 7000,
            'status' => ScheduleStatus::Partial,
        ]);
        $managerToken = $this->tokenFor($this->managerFor($tontine));
        Payment::factory()->create([
            'schedule_id' => $schedule->id,
            'recorded_by' => $this->managerFor($tontine)->id,
            'amount' => 7000,
        ]);

        $this->withToken($managerToken)
            ->postJson($this->paymentUrl($tontine, $schedule), ['amount' => 3001, 'method' => 'cash'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('payments', 1);

        $this->withToken($managerToken)
            ->postJson($this->paymentUrl($tontine, $schedule), ['amount' => 3000, 'method' => 'cash'])
            ->assertCreated();

        $this->withToken($managerToken)
            ->postJson($this->paymentUrl($tontine, $schedule), ['amount' => 1, 'method' => 'cash'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_manager_sees_all_schedules_and_payments_but_member_sees_only_their_own(): void
    {
        ['tontine' => $tontine, 'member' => $member, 'schedule' => $memberSchedule] = $this->createSchedule();
        $other = User::factory()->create();
        $otherMembership = TontineMember::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $other->id,
        ]);
        $otherSchedule = ContributionSchedule::factory()->create([
            'cycle_id' => $memberSchedule->cycle_id,
            'tontine_member_id' => $otherMembership->id,
        ]);
        $manager = $this->managerFor($tontine);
        ContributionSchedule::factory()->create([
            'cycle_id' => $memberSchedule->cycle_id,
            'tontine_member_id' => $tontine->members()
                ->where('role', MemberRole::Manager->value)
                ->firstOrFail()
                ->id,
        ]);
        Payment::factory()->create([
            'schedule_id' => $memberSchedule->id,
            'recorded_by' => $manager->id,
        ]);
        Payment::factory()->create([
            'schedule_id' => $otherSchedule->id,
            'recorded_by' => $manager->id,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->getJson('/api/v1/tontines/'.$tontine->public_id.'/schedules')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->withToken($this->tokenFor($manager))
            ->getJson('/api/v1/tontines/'.$tontine->public_id.'/payments')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($this->tokenFor($member))
            ->getJson('/api/v1/tontines/'.$tontine->public_id.'/schedules')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $memberSchedule->public_id);

        $this->withToken($this->tokenFor($member))
            ->getJson('/api/v1/tontines/'.$tontine->public_id.'/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.schedule.id', $memberSchedule->public_id);
    }

    public function test_non_manager_cannot_record_payments_and_non_members_receive_not_found(): void
    {
        ['tontine' => $tontine, 'member' => $member, 'schedule' => $schedule] = $this->createSchedule();
        $outsider = User::factory()->create();

        $this->withToken($this->tokenFor($member))
            ->postJson($this->paymentUrl($tontine, $schedule), ['amount' => 1000, 'method' => 'cash'])
            ->assertForbidden();

        $this->withToken($this->tokenFor($outsider))
            ->postJson($this->paymentUrl($tontine, $schedule), ['amount' => 0, 'method' => 'invalid'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_authentication_email_verification_and_invalid_input_are_enforced(): void
    {
        ['tontine' => $tontine, 'schedule' => $schedule] = $this->createSchedule();
        $url = $this->paymentUrl($tontine, $schedule);
        $manager = $this->managerFor($tontine);

        $this->postJson($url, ['amount' => 1000, 'method' => 'cash'])->assertUnauthorized();

        $unverifiedManager = User::factory()->unverified()->create();
        $unverifiedTontine = Tontine::factory()->create(['manager_id' => $unverifiedManager->id]);
        $unverifiedSchedule = $this->createScheduleFor($unverifiedTontine);
        $this->withToken($this->tokenFor($unverifiedManager))
            ->postJson($this->paymentUrl($unverifiedTontine, $unverifiedSchedule), [
                'amount' => 1000,
                'method' => 'cash',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');

        $this->withToken($this->tokenFor($manager))
            ->postJson($url, ['amount' => 0, 'method' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'method']);
    }

    public function test_schedule_identifier_is_scoped_to_its_tontine(): void
    {
        ['tontine' => $tontine, 'schedule' => $schedule] = $this->createSchedule();
        $otherTontine = Tontine::factory()->create();

        $this->withToken($this->tokenFor($this->managerFor($tontine)))
            ->postJson($this->paymentUrl($otherTontine, $schedule), [
                'amount' => 1000,
                'method' => 'cash',
            ])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);
    }

    public function test_overdue_status_is_calculated_and_daily_command_reports_without_changing_stored_status(): void
    {
        $this->travelTo(today()->addDays(10)->startOfDay());
        ['schedule' => $schedule] = $this->createSchedule([
            'due_date' => today()->subDays(3)->toDateString(),
        ]);

        $this->artisan('payments:report-overdue')
            ->expectsOutputToContain('1 échéance(s) en retard')
            ->assertExitCode(0);

        $this->assertSame(ScheduleStatus::Pending, $schedule->fresh()->status);
        $this->assertTrue($schedule->fresh()->isOverdue());
        $this->assertSame(3, $schedule->fresh()->daysLate());
    }

    public function test_inactive_member_cannot_access_tontine_payment_views(): void
    {
        ['tontine' => $tontine, 'member' => $member, 'membership' => $membership] = $this->createSchedule();
        $membership->status = MemberStatus::Inactive;
        $membership->save();

        $this->withToken($this->tokenFor($member))
            ->getJson('/api/v1/tontines/'.$tontine->public_id.'/payments')
            ->assertNotFound();
    }

    private function createScheduleFor(Tontine $tontine): ContributionSchedule
    {
        $membership = $tontine->members()->where('role', MemberRole::Manager->value)->firstOrFail();
        $cycle = Cycle::factory()->create(['tontine_id' => $tontine->id]);

        return ContributionSchedule::factory()->create([
            'cycle_id' => $cycle->id,
            'tontine_member_id' => $membership->id,
        ]);
    }
}
