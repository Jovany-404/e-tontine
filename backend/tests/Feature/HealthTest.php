<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_reports_ok(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_responses_carry_security_headers(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');

        // Laravel ajoute "private" à la valeur : on vérifie seulement la présence de no-store.
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_unknown_api_route_returns_json_not_html(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_protected_route_rejects_anonymous_users_with_json_401(): void
    {
        // Route temporaire, déclarée uniquement pour ce test.
        Route::middleware('auth:sanctum')->get('/api/v1/_probe', fn () => ['ok' => true]);

        $this->getJson('/api/v1/_probe')->assertUnauthorized();
    }

    public function test_cors_allows_the_frontend_origin(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/health')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function test_cors_never_allows_other_origins(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://evil.example',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/health');

        // Laravel peut renvoyer l'origine autorisée (localhost:5173) : le navigateur
        // refuse alors la requête, car elle ne correspond pas à l'origine réelle.
        // Ce qui ne doit jamais arriver : que l'origine étrangère soit autorisée.
        $this->assertNotSame('http://evil.example', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
