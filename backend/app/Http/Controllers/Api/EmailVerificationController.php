<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Cible du lien reçu par email. La route est protégée par le middleware "signed" :
     * l'URL ne peut être ni modifiée ni utilisée après expiration.
     * L'utilisateur clique depuis sa boîte mail : on le redirige vers le front avec un statut.
     */
    public function verify(string $id, string $hash): RedirectResponse
    {
        $user = User::where('public_id', $id)->first();

        // Le hash lie le lien à l'adresse email actuelle : si l'email change, l'ancien lien devient invalide.
        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->redirectToFrontend('invalid');
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectToFrontend('success');
    }

    /**
     * Renvoie l'email de vérification à l'utilisateur connecté.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('Your email address is already verified.')]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => __('Verification link sent.')]);
    }

    private function redirectToFrontend(string $status): RedirectResponse
    {
        return redirect()->away(config('etontine.frontend_url').'/email-verified?status='.$status);
    }
}
