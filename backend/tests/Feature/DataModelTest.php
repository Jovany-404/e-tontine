<?php

namespace Tests\Feature;

use App\Enums\Frequency;
use App\Enums\ScheduleStatus;
use App\Enums\TontineStatus;
use App\Models\ActivityLog;
use App\Models\ContributionSchedule;
use App\Models\Cycle;
use App\Models\Draw;
use App\Models\Invitation;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_get_a_public_ulid(): void
    {
        $user = User::factory()->create();
        $tontine = Tontine::factory()->create();

        $this->assertSame(26, strlen($user->public_id));
        $this->assertSame(26, strlen($tontine->public_id));
        $this->assertSame('public_id', $tontine->getRouteKeyName());
    }

    public function test_tontine_factory_registers_manager_as_member(): void
    {
        $tontine = Tontine::factory()->create();

        $this->assertDatabaseHas('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id' => $tontine->manager_id,
            'role' => 'manager',
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_be_member_twice_of_same_tontine(): void
    {
        $tontine = Tontine::factory()->create();
        $user = User::factory()->create();

        TontineMember::factory()->create(['tontine_id' => $tontine->id, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);

        TontineMember::factory()->create(['tontine_id' => $tontine->id, 'user_id' => $user->id]);
    }

    public function test_invitation_usability(): void
    {
        $valid = new Invitation(['is_active' => true, 'expires_at' => now()->addDay()]);
        $noExpiry = new Invitation(['is_active' => true, 'expires_at' => null]);
        $expired = new Invitation(['is_active' => true, 'expires_at' => now()->subMinute()]);
        $disabled = new Invitation(['is_active' => false, 'expires_at' => now()->addDay()]);

        $this->assertTrue($valid->isUsable());
        $this->assertTrue($noExpiry->isUsable());
        $this->assertFalse($expired->isUsable());
        $this->assertFalse($disabled->isUsable());
    }

    public function test_invitation_token_helpers(): void
    {
        $token = Invitation::generateToken();

        $this->assertSame(48, strlen($token));
        $this->assertNotSame($token, Invitation::generateToken());
        $this->assertSame(hash('sha256', 'abc'), Invitation::hashToken('abc'));
        $this->assertSame(64, strlen(Invitation::hashToken($token)));
    }

    public function test_schedule_overdue_is_computed(): void
    {
        $schedule = new ContributionSchedule();
        $schedule->amount_expected = 10000;
        $schedule->amount_paid = 4000;
        $schedule->status = ScheduleStatus::Partial;

        $schedule->due_date = today()->subDays(3);
        $this->assertTrue($schedule->isOverdue());
        $this->assertSame(3, $schedule->daysLate());
        $this->assertSame(6000, $schedule->remainingAmount());

        $schedule->status = ScheduleStatus::Paid;
        $this->assertFalse($schedule->isOverdue());
        $this->assertSame(0, $schedule->daysLate());

        $schedule->status = ScheduleStatus::Pending;
        $schedule->due_date = today()->addDays(2);
        $this->assertFalse($schedule->isOverdue());
    }

    public function test_same_member_cannot_win_twice_in_a_cycle(): void
    {
        $tontine = Tontine::factory()->create();
        $cycle = Cycle::factory()->create(['tontine_id' => $tontine->id]);
        $member = TontineMember::factory()->create(['tontine_id' => $tontine->id]);

        Draw::factory()->create([
            'cycle_id' => $cycle->id,
            'tontine_id' => $tontine->id,
            'round' => 1,
            'winner_member_id' => $member->id,
        ]);

        $this->expectException(QueryException::class);

        Draw::factory()->create([
            'cycle_id' => $cycle->id,
            'tontine_id' => $tontine->id,
            'round' => 2,
            'winner_member_id' => $member->id,
        ]);
    }

    public function test_a_round_has_only_one_draw(): void
    {
        $tontine = Tontine::factory()->create();
        $cycle = Cycle::factory()->create(['tontine_id' => $tontine->id]);
        $first = TontineMember::factory()->create(['tontine_id' => $tontine->id]);
        $second = TontineMember::factory()->create(['tontine_id' => $tontine->id]);

        Draw::factory()->create([
            'cycle_id' => $cycle->id,
            'tontine_id' => $tontine->id,
            'round' => 1,
            'winner_member_id' => $first->id,
        ]);

        $this->expectException(QueryException::class);

        Draw::factory()->create([
            'cycle_id' => $cycle->id,
            'tontine_id' => $tontine->id,
            'round' => 1,
            'winner_member_id' => $second->id,
        ]);
    }

    public function test_sensitive_fields_are_hidden(): void
    {
        $array = User::factory()->create()->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('two_factor_secret', $array);
    }

    public function test_role_is_per_tontine(): void
    {
        $tontineA = Tontine::factory()->create();
        $tontineB = Tontine::factory()->create();

        // Le gérant de A est simple membre de B.
        TontineMember::factory()->create([
            'tontine_id' => $tontineB->id,
            'user_id' => $tontineA->manager_id,
        ]);

        $user = User::find($tontineA->manager_id);

        $this->assertSame(1, $user->managedTontines()->count());
        $this->assertSame(2, $user->tontines()->count());
        $this->assertDatabaseHas('tontine_members', [
            'tontine_id' => $tontineB->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }

    public function test_enum_casts(): void
    {
        $tontine = Tontine::factory()->create();

        $this->assertInstanceOf(Frequency::class, $tontine->frequency);
        $this->assertSame(TontineStatus::Draft, $tontine->status);
    }

    public function test_activity_log_stores_metadata(): void
    {
        $tontine = Tontine::factory()->create();

        ActivityLog::create([
            'tontine_id' => $tontine->id,
            'user_id' => $tontine->manager_id,
            'action' => 'tontine.created',
            'subject_type' => 'tontine',
            'subject_id' => $tontine->id,
            'metadata' => ['name' => $tontine->name],
        ]);

        $log = ActivityLog::first();

        $this->assertSame('tontine.created', $log->action);
        $this->assertSame($tontine->name, $log->metadata['name']);
        $this->assertNotNull($log->created_at);
    }
}
