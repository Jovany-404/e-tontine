<?php

namespace Tests\Feature;

use App\Services\AuthenticationService;
use Tests\TestCase;

class LoginRouteTest extends TestCase
{
    public function test_api_login_route_exists_and_validates_input(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_api_login_returns_json_payload_with_token_for_valid_credentials(): void
    {
        $authService = $this->mock(AuthenticationService::class);
        $authService->shouldReceive('login')
            ->once()
            ->with(['email' => 'test@example.com', 'password' => 'password'])
            ->andReturn([
                'user' => ['id' => 1, 'email' => 'test@example.com'],
                'token' => 'test-token',
                'token_type' => 'Bearer',
            ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.token', 'test-token')
            ->assertJsonPath('data.user.email', 'test@example.com');
    }
}
