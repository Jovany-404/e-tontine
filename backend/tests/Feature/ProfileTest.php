<?php

namespace Tests\Feature;

use App\Enums\IdentityStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private const PROFILE = '/api/v1/user';
    private const PASSWORD = '/api/v1/user/password';

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function passwordPayload(array $overrides = []): array
    {
        return array_merge([
            'current_password' => 'Password123',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ], $overrides);
    }

    // ---------- Profil ----------

    public function test_user_can_update_name_phone_and_city(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->patchJson(self::PROFILE, [
                'name' => 'Awa Ndiaye',
                'phone' => '+221 77 999 88 77',
                'city' => 'Dakar',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Awa Ndiaye')
            ->assertJsonPath('data.city', 'Dakar');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Awa Ndiaye',
            'phone' => '+221 77 999 88 77',
            'city' => 'Dakar',
        ]);
    }

    public function test_email_cannot_be_changed_through_the_profile(): void
    {
        $user = User::factory()->create();
        $originalEmail = $user->email;

        $this->withToken($this->tokenFor($user))
            ->patchJson(self::PROFILE, ['name' => 'Awa Ndiaye', 'email' => 'hacker@example.com'])
            ->assertOk();

        $this->assertSame($originalEmail, $user->fresh()->email);
    }

    public function test_privileged_fields_are_ignored(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->patchJson(self::PROFILE, [
                'name' => 'Awa Ndiaye',
                'email_verified_at' => null,
                'phone_verified_at' => now()->toDateTimeString(),
                'identity_status' => 'verified',
            ])
            ->assertOk();

        $fresh = $user->fresh();

        $this->assertNotNull($fresh->email_verified_at);
        $this->assertNull($fresh->phone_verified_at);
        $this->assertSame(IdentityStatus::Unverified, $fresh->identity_status);
    }

    public function test_changing_the_phone_resets_its_verification(): void
    {
        $user = User::factory()->create([
            'phone' => '+221 77 000 00 00',
            'phone_verified_at' => now(),
        ]);

        $this->withToken($this->tokenFor($user))
            ->patchJson(self::PROFILE, ['phone' => '+221 77 111 11 11'])
            ->assertOk();

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_keeping_the_same_phone_keeps_its_verification(): void
    {
        $user = User::factory()->create([
            'phone' => '+221 77 000 00 00',
            'phone_verified_at' => now(),
        ]);

        $this->withToken($this->tokenFor($user))
            ->patchJson(self::PROFILE, ['phone' => '+221 77 000 00 00', 'city' => 'Thiès'])
            ->assertOk();

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->patchJson(self::PROFILE, ['phone' => 'not-a-phone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_profile_routes_require_authentication(): void
    {
        $this->patchJson(self::PROFILE, ['name' => 'Awa'])->assertUnauthorized();
        $this->putJson(self::PASSWORD, $this->passwordPayload())->assertUnauthorized();
    }

    // ---------- Mot de passe ----------

    public function test_password_change_keeps_current_device_and_revokes_the_others(): void
    {
        $user = User::factory()->create(); // mot de passe : Password123
        $current = $user->createToken('phone')->plainTextToken;
        $user->createToken('laptop');

        $this->withToken($current)
            ->putJson(self::PASSWORD, $this->passwordPayload())
            ->assertOk();

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));

        // Seul l'appareil courant reste connecté.
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'phone']);
    }

    public function test_password_change_rejects_a_wrong_current_password(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->putJson(self::PASSWORD, $this->passwordPayload(['current_password' => 'WrongPassword1']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('Password123', $user->fresh()->password));
    }

    public function test_password_change_rejects_weak_or_unconfirmed_passwords(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        foreach (['short1A', 'alllowercase123', 'NoNumbersHere'] as $weak) {
            $this->withToken($token)
                ->putJson(self::PASSWORD, $this->passwordPayload(['password' => $weak, 'password_confirmation' => $weak]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }

        $this->withToken($token)
            ->putJson(self::PASSWORD, $this->passwordPayload(['password_confirmation' => 'Different123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check('Password123', $user->fresh()->password));
    }

    public function test_new_password_must_differ_from_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->putJson(self::PASSWORD, $this->passwordPayload([
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_password_change_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        for ($i = 0; $i < 5; $i++) {
            $this->withToken($token)
                ->putJson(self::PASSWORD, $this->passwordPayload(['current_password' => 'WrongPassword'.$i]))
                ->assertUnprocessable();
        }

        // Après 5 tentatives, même la bonne requête est bloquée.
        $this->withToken($token)
            ->putJson(self::PASSWORD, $this->passwordPayload())
            ->assertStatus(429);
    }
}
