<?php

namespace App\Http\Controllers\Api;

use App\Enums\TontineStatus;
use App\Http\Controllers\Controller;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;

class PublicInvitationController extends Controller
{
    /**
     * Aperçu d'une tontine pour une personne qui a reçu un lien (sans compte requis).
     * Token inconnu, expiré, désactivé ou tontine terminée : toujours le même 404.
     */
    public function show(string $token, InvitationService $invitations): JsonResponse
    {
        $invitation = $invitations->findUsable($token);
        $tontine = $invitation?->tontine;

        if ($tontine === null || $tontine->status === TontineStatus::Completed) {
            abort(404);
        }

        // Minimisation : aucune donnée personnelle du gérant ni ID interne n'est exposé.
        return response()->json(['data' => [
            // Identifiant public nécessaire pour envoyer une demande, jamais l'ID interne.
            'tontine_public_id' => $tontine->public_id,
            'name' => $tontine->name,
            'type' => $tontine->type->value,
            'contribution_amount' => $tontine->contribution_amount,
            'frequency' => $tontine->frequency->value,
            'start_date' => $tontine->start_date->toDateString(),
            'expected_members' => $tontine->expected_members,
            'members_count' => $tontine->activeMembers()->count(),
            'manager_name' => $tontine->manager->name,
        ]]);
    }
}
