<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\Tontine;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations)
    {
    }

    /**
     * État du lien actif. Le token n'est jamais renvoyé : il n'est pas stocké en clair.
     */
    public function index(Tontine $tontine): JsonResponse
    {
        Gate::authorize('manageInvitations', $tontine);

        $current = $this->invitations->current($tontine);

        return response()->json(['data' => $current ? $this->describe($current) : null]);
    }

    /**
     * Génère un lien (et désactive l'ancien). Le lien complet n'est affiché que dans cette réponse.
     */
    public function store(StoreInvitationRequest $request, Tontine $tontine): JsonResponse
    {
        $data = $request->validated();

        $days = array_key_exists('expires_in_days', $data)
            ? $data['expires_in_days']
            : InvitationService::DEFAULT_DAYS;

        [$invitation, $token] = $this->invitations->generate($tontine, $request->user(), $days);

        return response()->json([
            'data' => $this->describe($invitation) + ['url' => $this->invitations->url($token)],
        ], 201);
    }

    /**
     * Désactive le lien actif.
     */
    public function destroy(Request $request, Tontine $tontine): JsonResponse
    {
        Gate::authorize('manageInvitations', $tontine);

        $this->invitations->disable($tontine, $request->user());

        return response()->json(null, 204);
    }

    private function describe(Invitation $invitation): array
    {
        return [
            'is_active' => $invitation->is_active,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
            'created_at' => $invitation->created_at?->toIso8601String(),
        ];
    }
}
