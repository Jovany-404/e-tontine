<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ResetAuthGuards;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Groupe "api" : on force le JSON en entrée, et on ajoute les en-têtes de sécurité en sortie.
        $middleware->api(
            prepend: [ResetAuthGuards::class, ForceJsonResponse::class],
            append: [SecurityHeaders::class],
        );

        // Active le limiteur "api" (défini dans AppServiceProvider) sur toutes les routes API.
        $middleware->throttleApi();

        // L'alias "verified" utilise notre version, qui répond en JSON avec un code stable.
        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Toute erreur sous /api répond en JSON, jamais en page HTML ni en redirection.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Messages traduits pour les erreurs générées par le framework.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => __('Unauthenticated.')], 401);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                // Les en-têtes (Retry-After...) sont conservés pour que le front sache quand réessayer.
                return response()->json(['message' => __('Too Many Attempts.')], 429, $e->getHeaders());
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') && $e->getStatusCode() === 404) {
                return response()->json(['message' => __('Not Found.')], 404);
            }
        });

        // Lien de vérification email invalide ou expiré : l'utilisateur clique depuis sa boîte mail,
        // on le renvoie donc vers le front avec un statut plutôt que d'afficher du JSON.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->routeIs('verification.verify')) {
                return redirect()->away(config('etontine.frontend_url').'/email-verified?status=invalid');
            }
        });
    })->create();
