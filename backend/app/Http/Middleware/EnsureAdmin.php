<?php

namespace App\Http\Middleware;

use Closure;

class EnsureAdmin
{
    public function handle($request, Closure $next): mixed
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin') {
            abort(response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les admins peuvent accéder à cette ressource.',
            ], 403));
        }

        return $next($request);
    }
}
