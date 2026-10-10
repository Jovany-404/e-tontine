<?php

namespace Tests\Feature;

use App\Enums\CycleStatus;
use App\Enums\MemberRole;
use App\Enums\TontineStatus;
use App\Models\ActivityLog;
use App\Models\ContributionSchedule;
use App\Models\Cycle;
use App\Models\Draw;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawTest extends TestCase
{
    use RefreshDatabase;

    private function createCycle(bool $fullyPaid = true): array
    {
        $tontine = Tontine::factory()->active()->create([
            'contribution_amount' => 10000,
            'expected_members' => 2,
        ]);
        $manager = User::findOrFail($tontine->manager_id);
        $member = User::factory()->create();
        $memberMembership = TontineMember::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $member->id,
        ]);
        $managerMembership = $tontine->members()
            ->where('role', MemberRole::Manager->value)
            ->firstOrFail();
        $cycle = Cycle::factory()->create(['tontine_id' => $tontine->id]);

        foreach ([1, 2] as $round) {
            foreach ([$managerMembership, $memberMembership] as $membership) {
                $schedule = ContributionSchedule::factory()->create([
                    'cycle_id' => $cycle->id,
                    'round' => $round,
                    'tontine_member_id' => $membership->id,
                    'amount_expected' => 10000,
                    'amount_paid' => $fullyPaid ? 10000 : 0,
                    'status' => $fullyPaid ? 'paid' : 'pending',
                ]);

                if ($fullyPaid) {
                    Payment::factory()->create([
                        'schedule_id' => $schedule->id,
                        'amount' => 10000,
                        'recorded_by' => $manager->id,
                    ]);
                }
            }
        }

        return compact('tontine', 'manager', 'member', 'cycle');
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function url(Tontine $tontine): string
    {
        return '/api/v1/tontines/'.$tontine->public_id.'/draws';
    }

    public function test_manager_draws_fully_paid_pot_and_records_a_public_result_and_activity(): void
    {
        ['tontine' => $tontine, 'manager' => $manager, 'member' => $member] = $this->createCycle();

        $response = $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine), [
                'amount' => 1,
                'winner_member_id' => -1,
                'drawn_by' => -1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.draw.cycle_number', 1)
            ->assertJsonPath('data.draw.round', 1)
            ->assertJsonPath('data.draw.amount', 20000)
            ->assertJsonPath('data.cycle_completed', false)
            ->assertJsonMissingPath('data.draw.tontine_id')
            ->assertJsonMissingPath('data.draw.drawn_by')
            ->assertJsonMissingPath('data.draw.winner_member_id');

        $draw = Draw::query()->where('public_id', $response->json('data.draw.id'))->firstOrFail();
        $this->assertSame($manager->id, $draw->drawn_by);
        $this->assertContains($draw->winner_member_id, [
            $tontine->members()->where('role', MemberRole::Manager->value)->value('id'),
            $tontine->members()->where('user_id', $member->id)->value('id'),
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $manager->id,
            'action' => 'draw.completed',
            'subject_type' => 'draw',
            'subject_id' => $draw->id,
        ]);
        $this->assertSame(CycleStatus::Open, $tontine->cycles()->firstOrFail()->fresh()->status);
        $this->assertSame(TontineStatus::Active, $tontine->fresh()->status);
    }

    public function test_draw_is_blocked_until_the_full_round_has_been_paid(): void
    {
        ['tontine' => $tontine, 'manager' => $manager] = $this->createCycle(fullyPaid: false);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payments');

        $this->assertDatabaseCount('draws', 0);
        $this->assertSame(0, ActivityLog::where('action', 'draw.completed')->count());
    }

    public function test_cycle_draws_in_order_and_each_member_wins_at_most_once_before_completion(): void
    {
        ['tontine' => $tontine, 'manager' => $manager, 'cycle' => $cycle] = $this->createCycle();
        $token = $this->tokenFor($manager);

        $firstResponse = $this->withToken($token)
            ->postJson($this->url($tontine))
            ->assertCreated()
            ->assertJsonPath('data.draw.round', 1)
            ->assertJsonPath('data.cycle_completed', false);
        $firstWinner = Draw::query()
            ->where('public_id', $firstResponse->json('data.draw.id'))
            ->value('winner_member_id');

        $this->withToken($token)
            ->postJson($this->url($tontine))
            ->assertCreated()
            ->assertJsonPath('data.draw.round', 2)
            ->assertJsonPath('data.cycle_completed', true)
            ->assertJsonPath('data.draw.amount', 20000);

        $draws = $cycle->draws()->orderBy('round')->get();
        $this->assertCount(2, $draws);
        $this->assertNotSame($firstWinner, $draws[1]->winner_member_id);
        $this->assertSame(CycleStatus::Closed, $cycle->fresh()->status);
        $this->assertNotNull($cycle->fresh()->closed_at);
        $this->assertSame(TontineStatus::Completed, $tontine->fresh()->status);

        $this->withToken($token)
            ->postJson($this->url($tontine))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cycle');

        $this->assertDatabaseCount('draws', 2);
    }

    public function test_manager_can_list_results_while_members_cannot_draw(): void
    {
        ['tontine' => $tontine, 'manager' => $manager, 'member' => $member] = $this->createCycle();

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->assertCreated();

        $this->withToken($this->tokenFor($member))
            ->getJson($this->url($tontine))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.round', 1)
            ->assertJsonPath('data.0.amount', 20000)
            ->assertJsonPath('data.0.winner.name', fn ($name) => is_string($name));

        $this->withToken($this->tokenFor($member))
            ->postJson($this->url($tontine))
            ->assertForbidden();
    }

    public function test_authentication_email_verification_and_tontine_membership_are_enforced(): void
    {
        ['tontine' => $tontine, 'manager' => $manager] = $this->createCycle();

        $this->postJson($this->url($tontine))->assertUnauthorized();

        $unverified = User::factory()->unverified()->create();
        $unverifiedTontine = Tontine::factory()->active()->create(['manager_id' => $unverified->id]);
        $this->withToken($this->tokenFor($unverified))
            ->postJson($this->url($unverifiedTontine))
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');

        $outsider = User::factory()->create();
        $this->withToken($this->tokenFor($outsider))
            ->postJson($this->url($tontine))
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url(Tontine::factory()->create()))
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);

        $this->assertDatabaseCount('draws', 0);
    }

    public function test_draw_endpoint_is_rate_limited(): void
    {
        ['tontine' => $tontine, 'manager' => $manager] = $this->createCycle(fullyPaid: false);
        $token = $this->tokenFor($manager);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withToken($token)
                ->postJson($this->url($tontine))
                ->assertUnprocessable();
        }

        $this->withToken($token)
            ->postJson($this->url($tontine))
            ->assertStatus(429);
    }
}
