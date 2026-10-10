<?php

namespace App\Services;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\RequestStatus;
use App\Enums\TontineStatus;
use App\Models\Invitation;
use App\Models\MembershipRequest;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use App\Notifications\MembershipRequestDecided;
use App\Notifications\MembershipRequestReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipRequestService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function submit(Tontine $tontine, User $user, string $invitationToken): MembershipRequest
    {
        return DB::transaction(function () use ($tontine, $user, $invitationToken): MembershipRequest {
            $tontine = Tontine::query()
                ->whereKey($tontine->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($tontine->status === TontineStatus::Completed) {
                abort(404);
            }

            $invitation = Invitation::query()
                ->usable()
                ->where('tontine_id', $tontine->id)
                ->where('token_hash', Invitation::hashToken($invitationToken))
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                abort(404);
            }

            $membership = TontineMember::query()
                ->where('tontine_id', $tontine->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($membership?->status === MemberStatus::Active) {
                throw ValidationException::withMessages([
                    'membership' => [__('You are already a member of this tontine.')],
                ]);
            }

            $membershipRequest = MembershipRequest::query()
                ->where('tontine_id', $tontine->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($membershipRequest?->status === RequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'membership_request' => [__('Your membership request is already pending.')],
                ]);
            }

            if ($membershipRequest?->status === RequestStatus::Approved) {
                throw ValidationException::withMessages([
                    'membership_request' => [__('Your membership request has already been approved.')],
                ]);
            }

            if ($membershipRequest === null) {
                $membershipRequest = $tontine->membershipRequests()->create([
                    'user_id' => $user->id,
                ]);
            } else {
                $membershipRequest->status = RequestStatus::Pending;
                $membershipRequest->decided_by = null;
                $membershipRequest->decided_at = null;
                $membershipRequest->reason = null;
                $membershipRequest->save();
            }

            $membershipRequest->setRelation('tontine', $tontine);
            $tontine->load('manager');

            $this->activityLogger->log('membership.requested', $user, $tontine, $membershipRequest);
            $tontine->manager->notify(new MembershipRequestReceived($membershipRequest));

            return $membershipRequest;
        });
    }

    public function approve(Tontine $tontine, MembershipRequest $membershipRequest, User $manager): MembershipRequest
    {
        return DB::transaction(function () use ($tontine, $membershipRequest, $manager): MembershipRequest {
            $tontine = Tontine::query()
                ->whereKey($tontine->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $membershipRequest = MembershipRequest::query()
                ->whereKey($membershipRequest->getKey())
                ->where('tontine_id', $tontine->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($membershipRequest);

            $memberCount = $tontine->activeMembers()->count();
            if ($memberCount >= $tontine->expected_members) {
                throw ValidationException::withMessages([
                    'membership_request' => [__('This tontine has reached its expected number of members.')],
                ]);
            }

            $membership = TontineMember::query()
                ->where('tontine_id', $tontine->id)
                ->where('user_id', $membershipRequest->user_id)
                ->lockForUpdate()
                ->first();

            if ($membership?->status === MemberStatus::Active) {
                throw ValidationException::withMessages([
                    'membership_request' => [__('This user is already a member of the tontine.')],
                ]);
            }

            if ($membership === null) {
                $tontine->members()->create([
                    'user_id' => $membershipRequest->user_id,
                    'role' => MemberRole::Member,
                    'status' => MemberStatus::Active,
                    'joined_at' => now(),
                ]);
            } else {
                $membership->fill([
                    'role' => MemberRole::Member,
                    'status' => MemberStatus::Active,
                    'joined_at' => now(),
                ])->save();
            }

            $membershipRequest->status = RequestStatus::Approved;
            $membershipRequest->decided_by = $manager->id;
            $membershipRequest->decided_at = now();
            $membershipRequest->save();
            $membershipRequest->setRelation('tontine', $tontine);
            $membershipRequest->setRelation('user', $membershipRequest->user()->firstOrFail());

            $this->activityLogger->log('membership.approved', $manager, $tontine, $membershipRequest);
            $membershipRequest->user->notify(new MembershipRequestDecided($membershipRequest));

            return $membershipRequest;
        });
    }

    public function reject(Tontine $tontine, MembershipRequest $membershipRequest, User $manager): MembershipRequest
    {
        return DB::transaction(function () use ($tontine, $membershipRequest, $manager): MembershipRequest {
            $tontine = Tontine::query()
                ->whereKey($tontine->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $membershipRequest = MembershipRequest::query()
                ->whereKey($membershipRequest->getKey())
                ->where('tontine_id', $tontine->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($membershipRequest);

            $membershipRequest->status = RequestStatus::Rejected;
            $membershipRequest->decided_by = $manager->id;
            $membershipRequest->decided_at = now();
            $membershipRequest->save();
            $membershipRequest->setRelation('tontine', $tontine);
            $membershipRequest->setRelation('user', $membershipRequest->user()->firstOrFail());

            $this->activityLogger->log('membership.rejected', $manager, $tontine, $membershipRequest);
            $membershipRequest->user->notify(new MembershipRequestDecided($membershipRequest));

            return $membershipRequest;
        });
    }

    private function ensurePending(MembershipRequest $membershipRequest): void
    {
        if ($membershipRequest->status !== RequestStatus::Pending) {
            throw ValidationException::withMessages([
                'membership_request' => [__('This membership request has already been decided.')],
            ]);
        }
    }
}
