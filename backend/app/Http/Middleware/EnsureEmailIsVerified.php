<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Bloque les routes sensibles tant que l'email n'est pas vérifié.
     * Réponse JSON avec un code stable, que le frontend utilise pour afficher le bon message.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => __('Unauthenticated.')], 401);
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('Your email address is not verified.'),
                'code' => 'email_not_verified',
            ], 403);
        }

        return $next($request);
    }
}
