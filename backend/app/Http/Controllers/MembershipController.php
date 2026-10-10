<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMembershipRequest;
use App\Http\Requests\UpdateMembershipRequest;
use App\Http\Resources\MembershipResource;
use App\Services\TontineService;
use App\Models\Membership;
use App\Models\Member;
use App\Models\Tontine;
use Illuminate\Http\JsonResponse;

class MembershipController extends Controller
{
    protected TontineService $tontineService;

    public function __construct(TontineService $tontineService)
    {
        $this->tontineService = $tontineService;
    }

    public function index(int $tontineId): JsonResponse
    {
        $tontine = Tontine::find($tontineId);

        if (!$tontine || $tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $memberships = $tontine->memberships()
            ->where('statut', 'actif')
            ->with('member')
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $memberships->toArray(),
        ], 200);
    }

    public function store(StoreMembershipRequest $request, int $tontineId): JsonResponse
    {
        $tontine = Tontine::find($tontineId);

        if (!$tontine || $tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $member = Member::find($request->member_id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé',
            ], 404);
        }

        $membership = $this->tontineService->addMember($tontine, [
            'member_id' => $request->member_id,
            'position' => $request->position ?? null,
            'member_nom' => $member->nom,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Membre ajouté à la tontine avec succès',
            'data' => new MembershipResource($membership),
        ], 201);
    }

    public function update(UpdateMembershipRequest $request, Membership $membership): JsonResponse
    {
        $tontine = $membership->tontine;

        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $request->validated();

        $membership->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Membership modifié avec succès',
            'data' => new MembershipResource($membership),
        ], 200);
    }

    public function remove(Membership $membership): JsonResponse
    {
        $tontine = $membership->tontine;

        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $this->tontineService->removeMember($tontine, $membership);

        return response()->json([
            'success' => true,
            'message' => 'Membre retiré de la tontine avec succès',
        ], 200);
    }
}
