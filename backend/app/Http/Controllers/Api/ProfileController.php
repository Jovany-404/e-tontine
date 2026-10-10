<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    /**
     * Met à jour le nom, le téléphone et la ville de l'utilisateur connecté.
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        // fill() n'accepte que les champs de $fillable (name, phone, city).
        $user->fill($request->validated());

        // Un numéro modifié n'est plus un numéro vérifié.
        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }

        $user->save();

        return UserResource::make($user);
    }

    /**
     * Change le mot de passe. L'appareil courant reste connecté, tous les autres sont déconnectés.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password')],
            ]);
        }

        $user->forceFill([
            'password' => $data['password'], // hashé automatiquement par le cast "hashed"
            'remember_token' => Str::random(60),
        ])->save();

        // Révocation des autres sessions : si quelqu'un utilisait un token volé, il est éjecté.
        $tokens = $user->tokens();
        $current = $user->currentAccessToken();

        if ($current instanceof PersonalAccessToken) {
            $tokens->where('id', '!=', $current->id);
        }

        $tokens->delete();

        return response()->json(['message' => __('Password updated.')]);
    }
}
