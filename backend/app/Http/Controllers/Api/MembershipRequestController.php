<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\StoreMembershipRequestRequest;
use App\Http\Resources\MembershipRequestResource;
use App\Models\MembershipRequest;
use App\Models\Tontine;
use App\Services\MembershipRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MembershipRequestController extends Controller
{
    public function __construct(private MembershipRequestService $membershipRequests) {}

    public function store(StoreMembershipRequestRequest $request, Tontine $tontine): JsonResponse
    {
        $membershipRequest = $this->membershipRequests->submit(
            $tontine,
            $request->user(),
            $request->validated('invitation_token'),
        );

        return (new MembershipRequestResource($membershipRequest))
            ->response()
            ->setStatusCode(202);
    }

    public function index(Tontine $tontine): JsonResponse
    {
        Gate::authorize('decideMembershipRequests', $tontine);

        $requests = $tontine->membershipRequests()
            ->pending()
            ->with('user:id,public_id,name')
            ->oldest()
            ->paginate(20);

        return MembershipRequestResource::collection($requests)->response();
    }

    public function mine(Tontine $tontine, Request $request): JsonResponse
    {
        $membershipRequest = $tontine->membershipRequests()
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'data' => $membershipRequest === null
                ? null
                : MembershipRequestResource::make($membershipRequest)->resolve(),
        ]);
    }

    public function approve(Tontine $tontine, MembershipRequest $membershipRequest, Request $request): JsonResponse
    {
        Gate::authorize('decideMembershipRequests', $tontine);

        $membershipRequest = $this->membershipRequests->approve($tontine, $membershipRequest, $request->user());

        return (new MembershipRequestResource($membershipRequest))->response();
    }

    public function reject(Tontine $tontine, MembershipRequest $membershipRequest, Request $request): JsonResponse
    {
        Gate::authorize('decideMembershipRequests', $tontine);

        $membershipRequest = $this->membershipRequests->reject($tontine, $membershipRequest, $request->user());

        return (new MembershipRequestResource($membershipRequest))->response();
    }
}
