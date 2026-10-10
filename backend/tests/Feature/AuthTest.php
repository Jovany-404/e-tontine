<?php

namespace Tests\Feature;

use App\Enums\IdentityStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER = '/api/v1/auth/register';
    private const LOGIN = '/api/v1/auth/login';
    private const FORGOT = '/api/v1/auth/forgot-password';
    private const RESET = '/api/v1/auth/reset-password';

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Awa Diop',
            'email' => 'awa@example.com',
            'phone' => '+221 77 123 45 67',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ], $overrides);
    }

    private function verificationUrl(User $user, ?string $hash = null, $expiresAt = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            $expiresAt ?? now()->addHour(),
            ['id' => $user->public_id, 'hash' => $hash ?? sha1($user->email)]
        );
    }

    private function frontendUrl(string $status): string
    {
        return config('etontine.frontend_url').'/email-verified?status='.$status;
    }

    // ---------- Inscription ----------

    public function test_user_can_register_and_receives_a_token(): void
    {
        Notification::fake();

        $response = $this->postJson(self::REGISTER, $this->payload());

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'email_verified'], 'token', 'token_type'])
            ->assertJsonPath('user.email_verified', false)
            ->assertJsonMissingPath('user.password');

        [$tokenId, $plainToken] = explode('|', $response->json('token'), 2);
        $this->assertNotEmpty($tokenId);
        $this->assertStringStartsWith('etn_', $plainToken);
        $this->assertDatabaseHas('users', ['email' => 'awa@example.com']);

        $user = User::where('email', 'awa@example.com')->first();
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }

    public function test_register_rejects_duplicate_email_case_insensitively(): void
    {
        User::factory()->create(['email' => 'awa@example.com']);

        $this->postJson(self::REGISTER, $this->payload(['email' => 'AWA@Example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_register_rejects_weak_passwords(): void
    {
        foreach (['short1A', 'alllowercase123', 'NoNumbersHere'] as $weak) {
            $this->postJson(self::REGISTER, $this->payload(['password' => $weak, 'password_confirmation' => $weak]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }

        $this->postJson(self::REGISTER, $this->payload(['password_confirmation' => 'Different123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_register_ignores_privileged_fields(): void
    {
        Notification::fake();

        $this->postJson(self::REGISTER, $this->payload([
            'email_verified_at' => now()->toDateTimeString(),
            'identity_status' => 'verified',
        ]))->assertCreated();

        $user = User::where('email', 'awa@example.com')->first();

        $this->assertNull($user->email_verified_at);
        $this->assertSame(IdentityStatus::Unverified, $user->identity_status);
    }

    // ---------- Connexion ----------

    public function test_login_returns_a_token(): void
    {
        User::factory()->create(['email' => 'awa@example.com']);

        $this->postJson(self::LOGIN, ['email' => 'awa@example.com', 'password' => 'Password123'])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token', 'token_type']);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['email' => 'awa@example.com']);

        $this->postJson(self::LOGIN, ['email' => 'awa@example.com', 'password' => 'WrongPassword1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_does_not_reveal_whether_an_email_exists(): void
    {
        User::factory()->create(['email' => 'awa@example.com']);

        $known = $this->postJson(self::LOGIN, ['email' => 'awa@example.com', 'password' => 'WrongPassword1'])
            ->json('errors.email.0');
        $unknown = $this->postJson(self::LOGIN, ['email' => 'ghost@example.com', 'password' => 'WrongPassword1'])
            ->json('errors.email.0');

        $this->assertNotNull($known);
        $this->assertSame($known, $unknown);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'awa@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::LOGIN, ['email' => 'awa@example.com', 'password' => 'Wrong'.$i.'Pass'])
                ->assertUnprocessable();
        }

        // Après 5 échecs, même le bon mot de passe est bloqué.
        $this->postJson(self::LOGIN, ['email' => 'awa@example.com', 'password' => 'Password123'])
            ->assertStatus(429);
    }

    // ---------- Utilisateur connecté ----------

    public function test_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_user_endpoint_returns_the_current_user_safely(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->public_id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.two_factor_secret');
    }

    // ---------- Déconnexion ----------

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('phone')->plainTextToken;
        $user->createToken('laptop');

        $this->withToken($current)->postJson('/api/v1/auth/logout')->assertOk();

        // Le token de l'autre appareil reste valide.
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('phone')->plainTextToken;
        $user->createToken('laptop');
        $user->createToken('tablet');

        $this->withToken($current)->postJson('/api/v1/auth/logout-all')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ---------- Mot de passe oublié ----------

    public function test_forgot_password_sends_a_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson(self::FORGOT, ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_does_not_reveal_whether_an_email_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->postJson(self::FORGOT, ['email' => $user->email]);
        $unknown = $this->postJson(self::FORGOT, ['email' => 'ghost@example.com']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));

        // Une seule notification : celle du compte existant.
        Notification::assertCount(1);
    }

    public function test_reset_link_points_to_the_frontend(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson(self::FORGOT, ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, config('etontine.frontend_url').'/reset-password?token=');
        });
    }

    public function test_password_can_be_reset_and_tokens_are_revoked(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->createToken('old-device');

        $this->postJson(self::FORGOT, ['email' => $user->email])->assertOk();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->postJson(self::RESET, [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_rejects_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson(self::RESET, [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertFalse(Hash::check('NewPassword456', $user->fresh()->password));
    }

    // ---------- Vérification de l'email ----------

    public function test_verification_link_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user))
            ->assertRedirect($this->frontendUrl('success'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verification_rejects_a_tampered_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user).'x')
            ->assertRedirect($this->frontendUrl('invalid'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_rejects_a_wrong_hash(): void
    {
        $user = User::factory()->unverified()->create();

        // Signature valide, mais le hash ne correspond pas à l'email du compte.
        $this->get($this->verificationUrl($user, sha1('someone-else@example.com')))
            ->assertRedirect($this->frontendUrl('invalid'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_rejects_an_expired_link(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user, null, now()->subMinute()))
            ->assertRedirect($this->frontendUrl('invalid'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/email/resend')->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_does_nothing_when_already_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/email/resend')->assertOk();

        Notification::assertNothingSent();
    }

    public function test_resend_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/email/resend')->assertUnauthorized();
    }

    // ---------- Configuration ----------

    public function test_token_expiration_and_prefix_are_configured(): void
    {
        $this->assertGreaterThan(0, (int) config('sanctum.expiration'));
        $this->assertSame('etn_', config('sanctum.token_prefix'));
    }
}
