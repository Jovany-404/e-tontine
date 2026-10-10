<?php

namespace App\Http\Controllers;

use App\Models\Tontine;
use Illuminate\Http\JsonResponse;

class DrawController extends Controller
{
    public function index(Tontine $tontine): JsonResponse
    {
        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $draws = $tontine->memberships()
            ->where('statut', 'actif')
            ->with('member')
            ->orderBy('position')
            ->get()
            ->map(function ($membership) {
                $member = $membership->member;

                return [
                    'id' => $membership->id,
                    'member_id' => $membership->member_id,
                    'winner_name' => $member?->nom_complet ?? $member?->nom ?? 'Membre',
                    'gagnant' => $member?->nom_complet ?? $member?->nom ?? 'Membre',
                    'position' => $membership->position,
                    'montant_distribue' => (float) ($membership->montant_engage ?? 0),
                    'montant' => (float) ($membership->montant_engage ?? 0),
                    'date_tirage' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $draws,
        ], 200);
    }

    public function generate(Tontine $tontine): JsonResponse
    {
        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $memberships = $tontine->memberships()
            ->where('statut', 'actif')
            ->with('member')
            ->get();

        if ($memberships->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun membre actif à tirer au sort',
            ], 422);
        }

        $shuffled = $memberships->shuffle();

        foreach ($shuffled as $index => $membership) {
            $membership->update([
                'position' => $index + 1,
            ]);
        }

        $draws = $tontine->memberships()
            ->where('statut', 'actif')
            ->with('member')
            ->orderBy('position')
            ->get()
            ->map(function ($membership) use ($tontine) {
                $member = $membership->member;

                return [
                    'id' => $membership->id,
                    'member_id' => $membership->member_id,
                    'winner_name' => $member?->nom_complet ?? $member?->nom ?? 'Membre',
                    'gagnant' => $member?->nom_complet ?? $member?->nom ?? 'Membre',
                    'position' => $membership->position,
                    'montant_distribue' => (float) ($tontine->montant_total ?? 0),
                    'montant' => (float) ($tontine->montant_total ?? 0),
                    'date_tirage' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Tirage effectué avec succès',
            'data' => $draws,
        ], 200);
    }
}
