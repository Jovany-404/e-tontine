<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticateUser
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if ($token) {
            $personalToken = PersonalAccessToken::findToken($token);

            if ($personalToken && $personalToken->tokenable) {
                Auth::login($personalToken->tokenable);

                return $next($request);
            }
        }

        if (Auth::check()) {
            return $next($request);
        }

        abort(response()->json([
            'success' => false,
            'message' => 'Utilisateur non authentifié',
        ], 401));
    }
}
