<?php

namespace App\Providers;

use App\Models\ContributionSchedule;
use App\Models\Cycle;
use App\Models\Draw;
use App\Models\Invitation;
use App\Models\MembershipRequest;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Hors production : exception en cas de lazy loading (N+1), d'attribut inconnu
        // ou d'assignation silencieusement ignorée. On détecte les erreurs tout de suite.
        Model::shouldBeStrict(! $this->app->isProduction());

        // La base stocke un alias court ("user", "tontine") plutôt que le nom de la classe PHP
        // dans les colonnes polymorphes (journal d'activité, notifications, tokens).
        Relation::morphMap([
            'user' => User::class,
            'tontine' => Tontine::class,
            'tontine_member' => TontineMember::class,
            'invitation' => Invitation::class,
            'membership_request' => MembershipRequest::class,
            'cycle' => Cycle::class,
            'contribution_schedule' => ContributionSchedule::class,
            'payment' => Payment::class,
            'draw' => Draw::class,
        ]);

        // Règle de mot de passe par défaut, utilisée via Password::defaults() dans les validations.
        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers();

            // Vérification des mots de passe déjà compromis : uniquement en production
            // (elle fait un appel réseau externe).
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        $this->configureAuthEmails();
        $this->configureRateLimiting();
    }

    /**
     * Personnalise les liens envoyés par email pour qu'ils fonctionnent avec une API + un front séparés.
     */
    private function configureAuthEmails(): void
    {
        // Lien de réinitialisation : ouvre la page du frontend React avec le token.
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('etontine.frontend_url').'/reset-password?token='.$token
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        // Lien de vérification : URL signée (infalsifiable) et expirante, qui pointe vers l'API.
        // On utilise le public_id (ULID) et non l'ID interne.
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->public_id,
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }

    /**
     * Limiteurs de requêtes, branchés sur les routes dans routes/api.php.
     */
    private function configureRateLimiting(): void
    {
        // Limite générale de l'API : par utilisateur connecté, sinon par IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user('sanctum')?->getAuthIdentifier() ?: $request->ip()));

        // Anti brute force sur la connexion : par couple email + IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        // Limite les créations de comptes en masse.
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)
            ->by($request->ip()));

        // Limite les demandes d'envoi de lien de réinitialisation.
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perHour(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        // Limite les tentatives de changement de mot de passe avec un token.
        RateLimiter::for('password-reset-confirm', fn (Request $request) => Limit::perHour(10)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        // Limite le renvoi de l'email de vérification : par utilisateur.
        RateLimiter::for('verification', fn (Request $request) => Limit::perHour(6)
            ->by($request->user('sanctum')?->getAuthIdentifier() ?: $request->ip()));

        // Consultation des liens d'invitation : empêche de deviner des tokens (Phase 6).
        RateLimiter::for('invitation', fn (Request $request) => Limit::perMinute(20)
            ->by($request->ip()));

        // Empêche l'envoi massif de demandes d'adhésion.
        RateLimiter::for('membership-request', fn (Request $request) => Limit::perHour(10)
            ->by($request->user('sanctum')?->getAuthIdentifier() ?: $request->ip()));

        // Évite les tentatives automatisées de démarrage des tontines.
        RateLimiter::for('tontine-start', fn (Request $request) => Limit::perHour(5)
            ->by($request->user('sanctum')?->getAuthIdentifier() ?: $request->ip()));

        // Limite l'enregistrement manuel des paiements.
        RateLimiter::for('payment-record', fn (Request $request) => Limit::perHour(60)
            ->by($request->user('sanctum')?->getAuthIdentifier() ?: $request->ip()));
    }
}
