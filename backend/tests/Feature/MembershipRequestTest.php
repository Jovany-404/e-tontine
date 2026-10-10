<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\RequestStatus;
use App\Models\Invitation;
use App\Models\MembershipRequest;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use App\Notifications\MembershipRequestDecided;
use App\Notifications\MembershipRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MembershipRequestTest extends TestCase
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
        return '/api/v1/tontines/'.$tontine->public_id.'/membership-requests';
    }

    private function makeInvitation(Tontine $tontine, array $attributes = []): string
    {
        $token = Invitation::generateToken();

        Invitation::factory()->forToken($token)->create(array_merge([
            'tontine_id' => $tontine->id,
            'created_by' => $tontine->manager_id,
        ], $attributes));

        return $token;
    }

    private function requestMembership(Tontine $tontine, User $user, string $invitationToken): TestResponse
    {
        return $this->withToken($this->tokenFor($user))
            ->postJson($this->url($tontine), ['invitation_token' => $invitationToken]);
    }

    private function decideUrl(Tontine $tontine, MembershipRequest $membershipRequest, string $decision): string
    {
        return $this->url($tontine).'/'.$membershipRequest->public_id.'/'.$decision;
    }

    public function test_invited_user_can_submit_a_request_and_manager_is_notified(): void
    {
        Notification::fake();
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $user = User::factory()->create();
        $token = $this->makeInvitation($tontine);

        $response = $this->requestMembership($tontine, $user, $token)
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonMissingPath('data.user')
            ->assertJsonMissingPath('data.user_id')
            ->assertJsonMissingPath('data.tontine_id');

        $this->assertDatabaseHas('membership_requests', [
            'public_id' => $response->json('data.id'),
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'decided_by' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'action' => 'membership.requested',
        ]);
        Notification::assertSentTo($manager, MembershipRequestReceived::class);
        $this->assertDatabaseMissing('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_invalid_wrong_tontine_expired_or_revoked_invitation_returns_the_same_404(): void
    {
        $tontine = $this->tontine();
        $otherTontine = $this->tontine();
        $user = User::factory()->create();
        $validToken = $this->makeInvitation($otherTontine);
        $expiredToken = $this->makeInvitation($tontine, ['expires_at' => now()->subMinute()]);
        $revokedToken = $this->makeInvitation($tontine, ['is_active' => false]);
        $responseBodies = [];

        foreach (['invalid-token', $validToken, $expiredToken, $revokedToken] as $token) {
            $response = $this->requestMembership($tontine, $user, $token)->assertNotFound();
            $responseBodies[] = $response->json();
        }

        foreach ($responseBodies as $body) {
            $this->assertSame(['message' => 'Not Found.'], $body);
        }

        $this->assertDatabaseCount('membership_requests', 0);
    }

    public function test_request_requires_authentication_and_a_verified_email(): void
    {
        $tontine = $this->tontine();
        $token = $this->makeInvitation($tontine);

        $this->postJson($this->url($tontine), ['invitation_token' => $token])->assertUnauthorized();

        $unverified = User::factory()->unverified()->create();
        $this->withToken($this->tokenFor($unverified))
            ->postJson($this->url($tontine), ['invitation_token' => $token])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');

        $this->assertDatabaseCount('membership_requests', 0);
    }

    public function test_missing_or_malformed_invitation_token_returns_not_found(): void
    {
        $tontine = $this->tontine();
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->postJson($this->url($tontine))
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);

        $this->withToken($this->tokenFor($user))
            ->postJson($this->url($tontine), ['invitation_token' => ['not', 'a', 'token']])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);

        $this->assertDatabaseCount('membership_requests', 0);
    }

    public function test_user_cannot_request_twice_or_join_when_already_active(): void
    {
        $tontine = $this->tontine();
        $user = User::factory()->create();
        $token = $this->makeInvitation($tontine);

        $this->requestMembership($tontine, $user, $token)->assertStatus(202);
        $this->requestMembership($tontine, $user, $token)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('membership_request');
        $this->assertDatabaseCount('membership_requests', 1);

        $member = User::factory()->create();
        TontineMember::factory()->create(['tontine_id' => $tontine->id, 'user_id' => $member->id]);
        $this->requestMembership($tontine, $member, $token)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('membership');
    }

    public function test_rejected_request_can_be_reopened_without_creating_a_second_record(): void
    {
        $tontine = $this->tontine();
        $user = User::factory()->create();
        $token = $this->makeInvitation($tontine);
        $existing = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'status' => RequestStatus::Rejected,
            'decided_by' => $tontine->manager_id,
            'decided_at' => now()->subDay(),
            'reason' => 'Refus précédent',
        ]);

        $this->requestMembership($tontine, $user, $token)
            ->assertStatus(202)
            ->assertJsonPath('data.id', $existing->public_id)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseCount('membership_requests', 1);
        $this->assertDatabaseHas('membership_requests', [
            'id' => $existing->id,
            'status' => 'pending',
            'decided_by' => null,
            'decided_at' => null,
            'reason' => null,
        ]);
    }

    public function test_manager_sees_only_pending_requests_with_minimal_applicant_data(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $pendingUser = User::factory()->create(['phone' => '0700000000']);
        $pending = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $pendingUser->id,
        ]);
        MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'status' => RequestStatus::Rejected,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->getJson($this->url($tontine))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->public_id)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.user.id', $pendingUser->public_id)
            ->assertJsonPath('data.0.user.name', $pendingUser->name)
            ->assertJsonMissingPath('data.0.user.email')
            ->assertJsonMissingPath('data.0.user.phone')
            ->assertJsonMissingPath('data.0.user_id');
    }

    public function test_applicant_can_read_only_their_own_request_status(): void
    {
        $tontine = $this->tontine();
        $user = User::factory()->create();
        $ownRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);
        MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'status' => RequestStatus::Rejected,
        ]);
        $url = $this->url($tontine).'/mine';

        $this->getJson($url)->assertUnauthorized();

        $this->withToken($this->tokenFor(User::factory()->unverified()->create()))
            ->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');

        $this->withToken($this->tokenFor($user))
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.id', $ownRequest->public_id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonMissingPath('data.user')
            ->assertJsonMissingPath('data.user_id')
            ->assertJsonMissingPath('data.decided_by');

        $newUser = User::factory()->create();
        $this->withToken($this->tokenFor($newUser))
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_manager_can_approve_request_with_atomic_membership_activity_and_notification(): void
    {
        Notification::fake();
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $user = User::factory()->create();
        $membershipRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->decideUrl($tontine, $membershipRequest, 'approve'))
            ->assertOk()
            ->assertJsonPath('data.id', $membershipRequest->public_id)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'role' => MemberRole::Member->value,
            'status' => MemberStatus::Active->value,
        ]);
        $this->assertDatabaseHas('membership_requests', [
            'id' => $membershipRequest->id,
            'status' => 'approved',
            'decided_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $manager->id,
            'action' => 'membership.approved',
        ]);
        Notification::assertSentTo($user, MembershipRequestDecided::class);
    }

    public function test_manager_can_reject_a_request_without_adding_a_member(): void
    {
        Notification::fake();
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $user = User::factory()->create();
        $membershipRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->decideUrl($tontine, $membershipRequest, 'reject'))
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('membership_requests', [
            'id' => $membershipRequest->id,
            'status' => 'rejected',
            'decided_by' => $manager->id,
        ]);
        $this->assertDatabaseMissing('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $manager->id,
            'action' => 'membership.rejected',
        ]);
        Notification::assertSentTo($user, MembershipRequestDecided::class);
    }

    public function test_decided_request_cannot_be_decided_again(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $user = User::factory()->create();
        $membershipRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'status' => RequestStatus::Rejected,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->decideUrl($tontine, $membershipRequest, 'approve'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('membership_request');

        $this->assertDatabaseMissing('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_expected_member_limit_includes_the_manager_and_blocks_approval(): void
    {
        $tontine = $this->tontine(['expected_members' => 1]);
        $manager = $this->managerOf($tontine);
        $user = User::factory()->create();
        $membershipRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->decideUrl($tontine, $membershipRequest, 'approve'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('membership_request');

        $this->assertDatabaseHas('membership_requests', [
            'id' => $membershipRequest->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'membership.approved']);
    }

    public function test_approval_reactivates_an_existing_inactive_membership(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $user = User::factory()->create();
        $membership = TontineMember::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'status' => MemberStatus::Inactive,
        ]);
        $membershipRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->decideUrl($tontine, $membershipRequest, 'approve'))
            ->assertOk();

        $this->assertDatabaseCount('tontine_members', 2);
        $this->assertDatabaseHas('tontine_members', [
            'id' => $membership->id,
            'status' => 'active',
            'role' => 'member',
        ]);
    }

    public function test_member_cannot_list_or_decide_requests(): void
    {
        $tontine = $this->tontine();
        $member = User::factory()->create();
        TontineMember::factory()->create(['tontine_id' => $tontine->id, 'user_id' => $member->id]);
        $membershipRequest = MembershipRequest::factory()->create(['tontine_id' => $tontine->id]);
        $token = $this->tokenFor($member);

        $this->withToken($token)->getJson($this->url($tontine))->assertForbidden();
        $this->withToken($token)
            ->postJson($this->decideUrl($tontine, $membershipRequest, 'approve'))
            ->assertForbidden();
        $this->withToken($token)
            ->postJson($this->decideUrl($tontine, $membershipRequest, 'reject'))
            ->assertForbidden();
    }

    public function test_outsider_and_manager_of_another_tontine_get_404(): void
    {
        $tontine = $this->tontine();
        $membershipRequest = MembershipRequest::factory()->create(['tontine_id' => $tontine->id]);
        $outsider = User::factory()->create();
        $foreignManager = $this->managerOf($this->tontine());

        foreach ([$outsider, $foreignManager] as $user) {
            $token = $this->tokenFor($user);
            $this->withToken($token)->getJson($this->url($tontine))->assertNotFound();
            $this->withToken($token)
                ->postJson($this->decideUrl($tontine, $membershipRequest, 'approve'))
                ->assertNotFound();
        }
    }

    public function test_nested_membership_request_cannot_be_accessed_through_another_tontine(): void
    {
        $tontine = $this->tontine();
        $otherTontine = $this->tontine();
        $membershipRequest = MembershipRequest::factory()->create(['tontine_id' => $tontine->id]);
        $manager = $this->managerOf($otherTontine);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->decideUrl($otherTontine, $membershipRequest, 'approve'))
            ->assertNotFound();

        $this->assertDatabaseHas('membership_requests', [
            'id' => $membershipRequest->id,
            'status' => 'pending',
        ]);
    }

    public function test_request_ignores_client_supplied_decision_and_identity_fields(): void
    {
        $tontine = $this->tontine();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $this->makeInvitation($tontine);

        $response = $this->withToken($this->tokenFor($user))
            ->postJson($this->url($tontine), [
                'invitation_token' => $token,
                'user_id' => $otherUser->id,
                'status' => 'approved',
                'decided_by' => $tontine->manager_id,
                'reason' => 'Client supplied',
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending');

        $request = MembershipRequest::where('public_id', $response->json('data.id'))->firstOrFail();
        $this->assertSame($user->id, $request->user_id);
        $this->assertNull($request->decided_by);
        $this->assertNull($request->reason);
    }

    public function test_membership_request_endpoint_is_rate_limited(): void
    {
        $tontine = $this->tontine();
        $token = $this->makeInvitation($tontine);
        $user = User::factory()->create();
        $accessToken = $this->tokenFor($user);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $response = $this->withToken($accessToken)
                ->postJson($this->url($tontine), ['invitation_token' => $token]);

            $this->assertContains($response->status(), [202, 422]);
        }

        $this->withToken($accessToken)
            ->postJson($this->url($tontine), ['invitation_token' => $token])
            ->assertStatus(429);
    }
}
