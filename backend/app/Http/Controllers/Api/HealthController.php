<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Vérifie que l'API répond et que la base de données est joignable.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            // Aucun détail d'erreur n'est exposé : il serait lisible par n'importe qui.
            return response()->json(['status' => 'error'], 503);
        }

        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
        ]);
    }
}
