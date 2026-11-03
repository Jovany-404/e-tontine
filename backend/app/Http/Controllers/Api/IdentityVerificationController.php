<?php

namespace App\Http\Controllers\Api;

use App\Enums\IdentityStatus;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\StoreIdentityVerificationRequest;
use App\Http\Resources\IdentityVerificationResource;
use App\Models\IdentityVerification;
use App\Models\MembershipRequest;
use App\Models\Tontine;
use App\Services\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IdentityVerificationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $verification = IdentityVerification::query()
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'data' => $verification === null
                ? null
                : IdentityVerificationResource::make($verification)->resolve(),
        ]);
    }

    public function store(
        StoreIdentityVerificationRequest $request,
        IdentityVerificationService $identity,
    ): JsonResponse {
        $data = $request->validated();
        $verification = $identity->submit(
            $request->user(),
            $data['document_type'],
            $request->file('document_front'),
            $request->file('document_back'),
            $request->file('profile_photo'),
        );

        return response()->json([
            'data' => IdentityVerificationResource::make($verification)->resolve(),
        ], 202);
    }

    public function profilePhoto(Request $request, IdentityVerificationService $identity)
    {
        $verification = IdentityVerification::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $file = $identity->fileContents($verification, 'profile');

        return response($file['contents'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function file(
        Tontine $tontine,
        MembershipRequest $membershipRequest,
        string $kind,
        IdentityVerificationService $identity,
    ) {
        Gate::authorize('decideMembershipRequests', $tontine);
        $this->ensurePendingApplicant($tontine, $membershipRequest);

        $verification = $this->pendingVerification($membershipRequest);
        $file = $identity->fileContents($verification, $kind);

        return response($file['contents'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function approve(
        Request $request,
        Tontine $tontine,
        MembershipRequest $membershipRequest,
        IdentityVerificationService $identity,
    ): JsonResponse {
        Gate::authorize('decideMembershipRequests', $tontine);
        $this->ensurePendingApplicant($tontine, $membershipRequest);

        $reviewed = $identity->review(
            $this->pendingVerification($membershipRequest),
            $request->user(),
            IdentityStatus::Verified,
            $tontine,
        );

        return response()->json([
            'data' => IdentityVerificationResource::make($reviewed)->resolve(),
        ]);
    }

    public function reject(
        Request $request,
        Tontine $tontine,
        MembershipRequest $membershipRequest,
        IdentityVerificationService $identity,
    ): JsonResponse {
        Gate::authorize('decideMembershipRequests', $tontine);
        $this->ensurePendingApplicant($tontine, $membershipRequest);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $reviewed = $identity->review(
            $this->pendingVerification($membershipRequest),
            $request->user(),
            IdentityStatus::Rejected,
            $tontine,
            $data['reason'],
        );

        return response()->json([
            'data' => IdentityVerificationResource::make($reviewed)->resolve(),
        ]);
    }

    private function ensurePendingApplicant(Tontine $tontine, MembershipRequest $membershipRequest): void
    {
        if ($membershipRequest->tontine_id !== $tontine->id
            || $membershipRequest->status !== RequestStatus::Pending) {
            abort(404);
        }
    }

    private function pendingVerification(MembershipRequest $membershipRequest): IdentityVerification
    {
        return IdentityVerification::query()
            ->where('user_id', $membershipRequest->user_id)
            ->where('status', IdentityStatus::Pending->value)
            ->firstOrFail();
    }
}
