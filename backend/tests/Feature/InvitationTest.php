<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invitation;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Aides ----------

    private function tontine(array $attributes = []): Tontine
    {
        // La factory inscrit aussi le gérant comme membre (rôle manager).
        return Tontine::factory()->create($attributes);
    }

    private function managerOf(Tontine $tontine): User
    {
        return User::findOrFail($tontine->manager_id);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function url(Tontine $tontine): string
    {
        return '/api/v1/tontines/'.$tontine->public_id.'/invitations';
    }

    private function publicUrl(string $token): string
    {
        return '/api/v1/invitations/'.$token;
    }

    // Crée une invitation dont on connaît le token en clair.
    private function makeInvitation(Tontine $tontine, array $attributes = []): string
    {
        $token = Invitation::generateToken();

        Invitation::factory()->forToken($token)->create(array_merge([
            'tontine_id' => $tontine->id,
            'created_by' => $tontine->manager_id,
        ], $attributes));

        return $token;
    }

    // Extrait le token en clair d'un lien complet.
    private function tokenFromUrl(string $url): string
    {
        return basename($url);
    }

    // ---------- Génération ----------

    public function test_manager_can_generate_a_link_and_only_the_hash_is_stored(): void
    {
        $tontine = $this->tontine();

        $response = $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->postJson($this->url($tontine))
            ->assertCreated()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => ['url', 'expires_at', 'created_at']]);

        $url = $response->json('data.url');
        $token = $this->tokenFromUrl($url);

        $this->assertStringStartsWith(config('etontine.frontend_url').'/join/', $url);
        $this->assertSame(48, strlen($token));

        $this->assertDatabaseHas('invitations', [
            'tontine_id' => $tontine->id,
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        // Le token en clair n'est stocké nulle part.
        $this->assertDatabaseMissing('invitations', ['token_hash' => $token]);
    }

    public function test_expiration_defaults_to_seven_days_and_can_be_customised_or_removed(): void
    {
        $tontine = $this->tontine();
        $token = $this->tokenFor($this->managerOf($tontine));

        $default = $this->withToken($token)->postJson($this->url($tontine))->assertCreated();
        $this->assertEqualsWithDelta(
            now()->addDays(7)->timestamp,
            Carbon::parse($default->json('data.expires_at'))->timestamp,
            10
        );

        $custom = $this->withToken($token)
            ->postJson($this->url($tontine), ['expires_in_days' => 30])->assertCreated();
        $this->assertEqualsWithDelta(
            now()->addDays(30)->timestamp,
            Carbon::parse($custom->json('data.expires_at'))->timestamp,
            10
        );

        $never = $this->withToken($token)
            ->postJson($this->url($tontine), ['expires_in_days' => null])->assertCreated();
        $this->assertNull($never->json('data.expires_at'));
    }

    public function test_expiration_is_validated(): void
    {
        $tontine = $this->tontine();
        $token = $this->tokenFor($this->managerOf($tontine));

        foreach ([0, 91, 'abc'] as $invalid) {
            $this->withToken($token)
                ->postJson($this->url($tontine), ['expires_in_days' => $invalid])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('expires_in_days');
        }
    }

    public function test_regenerating_revokes_the_previous_link(): void
    {
        $tontine = $this->tontine();
        $token = $this->tokenFor($this->managerOf($tontine));

        $first = $this->tokenFromUrl(
            $this->withToken($token)->postJson($this->url($tontine))->json('data.url')
        );
        $second = $this->tokenFromUrl(
            $this->withToken($token)->postJson($this->url($tontine))->json('data.url')
        );

        $this->getJson($this->publicUrl($first))->assertNotFound();
        $this->getJson($this->publicUrl($second))->assertOk();

        $this->assertSame(1, Invitation::where('tontine_id', $tontine->id)->where('is_active', true)->count());
    }

    public function test_cannot_generate_a_link_for_a_completed_tontine(): void
    {
        $tontine = $this->tontine(['status' => 'completed']);

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->postJson($this->url($tontine))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tontine');

        $this->assertDatabaseCount('invitations', 0);
    }

    // ---------- Permissions ----------

    public function test_member_cannot_manage_invitations(): void
    {
        $tontine = $this->tontine();
        $member = User::factory()->create();
        TontineMember::factory()->create(['tontine_id' => $tontine->id, 'user_id' => $member->id]);
        $token = $this->tokenFor($member);

        $this->withToken($token)->getJson($this->url($tontine))->assertForbidden();
        $this->withToken($token)->postJson($this->url($tontine))->assertForbidden();
        $this->withToken($token)->deleteJson($this->url($tontine))->assertForbidden();

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_outsider_and_foreign_manager_get_404(): void
    {
        $tontine = $this->tontine();
        $outsider = User::factory()->create();
        $foreignManager = $this->managerOf($this->tontine());

        foreach ([$outsider, $foreignManager] as $user) {
            $token = $this->tokenFor($user);

            $this->withToken($token)->getJson($this->url($tontine))->assertNotFound();
            $this->withToken($token)->postJson($this->url($tontine))->assertNotFound();
            $this->withToken($token)->deleteJson($this->url($tontine))->assertNotFound();
        }

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_unverified_manager_is_blocked(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $manager->forceFill(['email_verified_at' => null])->save();

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');
    }

    public function test_authentication_is_required(): void
    {
        $tontine = $this->tontine();

        $this->getJson($this->url($tontine))->assertUnauthorized();
        $this->postJson($this->url($tontine))->assertUnauthorized();
        $this->deleteJson($this->url($tontine))->assertUnauthorized();
    }

    // ---------- Consultation et désactivation (gérant) ----------

    public function test_index_returns_null_when_there_is_no_active_link(): void
    {
        $tontine = $this->tontine();

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->getJson($this->url($tontine))
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_index_describes_the_active_link_without_exposing_the_token(): void
    {
        $tontine = $this->tontine();
        $token = $this->makeInvitation($tontine);

        $response = $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->getJson($this->url($tontine))
            ->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.url')
            ->assertJsonMissingPath('data.token_hash')
            ->assertJsonMissingPath('data.id');

        $this->assertStringNotContainsString($token, $response->getContent());
        $this->assertStringNotContainsString(hash('sha256', $token), $response->getContent());
    }

    public function test_manager_can_disable_the_link(): void
    {
        $tontine = $this->tontine();
        $token = $this->makeInvitation($tontine);

        $this->getJson($this->publicUrl($token))->assertOk();

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->deleteJson($this->url($tontine))
            ->assertNoContent();

        $this->getJson($this->publicUrl($token))->assertNotFound();

        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'action' => 'invitation.disabled',
        ]);
    }

    // ---------- Aperçu public ----------

    public function test_public_preview_shows_only_minimal_information(): void
    {
        $tontine = $this->tontine(['name' => 'Tontine Famille', 'contribution_amount' => 10000]);
        $manager = $this->managerOf($tontine);
        $token = $this->makeInvitation($tontine);

        $response = $this->getJson($this->publicUrl($token))
            ->assertOk()
            ->assertJsonPath('data.name', 'Tontine Famille')
            ->assertJsonPath('data.contribution_amount', 10000)
            ->assertJsonPath('data.manager_name', $manager->name)
            ->assertJsonPath('data.members_count', 1)
            ->assertJsonPath('data.tontine_public_id', $tontine->public_id)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.manager_id')
            ->assertJsonMissingPath('data.rules');

        $this->assertStringNotContainsString($manager->email, $response->getContent());
        $this->assertStringNotContainsString((string) $manager->phone, $response->getContent());
    }

    public function test_every_invalid_token_gets_the_exact_same_404(): void
    {
        $tontine = $this->tontine();

        $expired = $this->makeInvitation($tontine, ['expires_at' => now()->subMinute()]);
        $disabled = $this->makeInvitation($tontine, ['is_active' => false]);

        $reference = $this->getJson($this->publicUrl(Invitation::generateToken()));
        $reference->assertNotFound();

        foreach ([$expired, $disabled, 'abc', str_repeat('Z', 47)] as $token) {
            $response = $this->getJson($this->publicUrl($token));

            $response->assertNotFound();
            $this->assertSame($reference->json(), $response->json());
        }
    }

    public function test_link_with_no_expiration_stays_valid(): void
    {
        $tontine = $this->tontine();
        $token = $this->makeInvitation($tontine, ['expires_at' => null]);

        $this->getJson($this->publicUrl($token))->assertOk();
    }

    public function test_public_preview_is_hidden_for_a_completed_tontine(): void
    {
        $tontine = $this->tontine(['status' => 'completed']);
        $token = $this->makeInvitation($tontine);

        $this->getJson($this->publicUrl($token))->assertNotFound();
    }

    public function test_public_preview_is_rate_limited(): void
    {
        $unknown = Invitation::generateToken();

        for ($i = 0; $i < 20; $i++) {
            $this->getJson($this->publicUrl($unknown))->assertNotFound();
        }

        $this->getJson($this->publicUrl($unknown))->assertStatus(429);
    }

    // ---------- Journal ----------

    public function test_generation_is_logged_without_the_token(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);

        $url = $this->withToken($this->tokenFor($manager))
            ->postJson($this->url($tontine))
            ->json('data.url');

        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $manager->id,
            'action' => 'invitation.created',
        ]);

        // Ni le token, ni son hash ne doivent apparaître dans le journal.
        $token = $this->tokenFromUrl($url);
        $logs = ActivityLog::all()->toJson();

        $this->assertStringNotContainsString($token, $logs);
        $this->assertStringNotContainsString(hash('sha256', $token), $logs);
    }
}
