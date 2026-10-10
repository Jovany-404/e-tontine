<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Envoie le lien de réinitialisation.
     * La réponse est toujours la même, que l'email existe ou non (pas d'énumération de comptes).
     */
    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => __('If an account exists for this email, a reset link has been sent.'),
        ]);
    }

    /**
     * Change le mot de passe à partir du token reçu par email.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // hashé par le cast "hashed"
                    'remember_token' => Str::random(60),
                ])->save();

                // Après un changement de mot de passe, toutes les sessions existantes sont fermées.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Message unique pour token invalide, expiré ou email inconnu.
            throw ValidationException::withMessages([
                'email' => [__('passwords.token')],
            ]);
        }

        return response()->json(['message' => __('passwords.reset')]);
    }
}
