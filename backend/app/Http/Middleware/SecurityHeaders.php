<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Ajoute les en-têtes de sécurité HTTP à toutes les réponses de l'API.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Empêche le navigateur de deviner le type MIME d'une réponse.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Interdit l'affichage de l'API dans une iframe.
        $response->headers->set('X-Frame-Options', 'DENY');
        // N'envoie pas l'URL d'origine aux autres sites.
        $response->headers->set('Referrer-Policy', 'no-referrer');
        // Les réponses contiennent des données personnelles : pas de mise en cache.
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
