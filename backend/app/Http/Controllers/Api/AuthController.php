<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Inscription : crée le compte, déclenche l'email de vérification et renvoie un token.
     * Seuls les champs validés sont utilisés : un client ne peut pas forcer email_verified_at, etc.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'], // hashé automatiquement par le cast "hashed"
        ])->refresh();

        // L'événement Registered envoie l'email de vérification.
        event(new Registered($user));

        return $this->tokenResponse($user, $data['device_name'] ?? null, 201);
    }

    /**
     * Connexion. Le message d'erreur est identique que l'email existe ou non.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if ($user === null) {
            // Dépense le même temps de calcul qu'une vraie vérification de mot de passe,
            // pour qu'on ne puisse pas deviner qu'un email existe en mesurant le temps de réponse.
            Hash::make($data['password']);
            $this->failLogin();
        }

        if (! Hash::check($data['password'], $user->password)) {
            $this->failLogin();
        }

        return $this->tokenResponse($user, $data['device_name'] ?? null);
    }

    /**
     * Déconnexion de l'appareil courant : seul le token utilisé est révoqué.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => __('Logged out.')]);
    }

    /**
     * Déconnexion de tous les appareils : tous les tokens de l'utilisateur sont révoqués.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => __('All sessions have been closed.')]);
    }

    private function tokenResponse(User $user, ?string $deviceName, int $status = 200): JsonResponse
    {
        // La durée de vie du token vient de config/sanctum.php (SANCTUM_TOKEN_EXPIRATION).
        $token = $user->createToken($deviceName ?: 'web')->plainTextToken;

        return response()->json([
            'user' => UserResource::make($user)->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], $status);
    }

    private function failLogin(): never
    {
        throw ValidationException::withMessages([
            'email' => [__('auth.failed')],
        ]);
    }
}
