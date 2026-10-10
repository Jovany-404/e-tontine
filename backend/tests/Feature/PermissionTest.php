<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    // Droits réservés au gérant de la tontine.
    private const MANAGER_ABILITIES = [
        'update',
        'manageInvitations',
        'decideMembershipRequests',
        'recordPayments',
        'viewAllPayments',
        'draw',
        'viewActivity',
    ];

    // Droits de tout membre actif.
    private const MEMBER_ABILITIES = ['view', 'viewMembers'];

    // ---------- Aides ----------

    private function tontine(): Tontine
    {
        // La factory inscrit aussi le gérant comme membre (rôle manager).
        return Tontine::factory()->create();
    }

    private function managerOf(Tontine $tontine): User
    {
        return User::findOrFail($tontine->manager_id);
    }

    private function addMember(Tontine $tontine, array $attributes = []): User
    {
        $user = User::factory()->create();

        TontineMember::factory()->create(array_merge(
            ['tontine_id' => $tontine->id, 'user_id' => $user->id],
            $attributes
        ));

        return $user;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    // Routes temporaires pour tester la chaîne HTTP complète (liaison par public_id, policy, JSON).
    private function registerProbes(): void
    {
        Route::middleware(['api', 'auth:sanctum'])->get('/api/v1/_probe/{tontine}', function (Tontine $tontine) {
            Gate::authorize('update', $tontine);

            return response()->json(['ok' => true]);
        });

        Route::middleware(['api', 'auth:sanctum', 'verified'])->get('/api/v1/_probe-verified', function () {
            return response()->json(['ok' => true]);
        });
    }

    // ---------- Policy ----------

    public function test_manager_has_every_tontine_ability(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);

        foreach (array_merge(self::MEMBER_ABILITIES, self::MANAGER_ABILITIES) as $ability) {
            $this->assertTrue($manager->can($ability, $tontine), "Le gérant devrait avoir : {$ability}");
        }
    }

    public function test_member_can_only_view_the_tontine_and_its_members(): void
    {
        $tontine = $this->tontine();
        $member = $this->addMember($tontine);

        foreach (self::MEMBER_ABILITIES as $ability) {
            $this->assertTrue($member->can($ability, $tontine), "Le membre devrait avoir : {$ability}");
        }

        foreach (self::MANAGER_ABILITIES as $ability) {
            $this->assertFalse($member->can($ability, $tontine), "Le membre ne devrait pas avoir : {$ability}");
        }
    }

    public function test_member_denial_is_a_403_not_a_404(): void
    {
        $tontine = $this->tontine();
        $member = $this->addMember($tontine);

        $response = Gate::forUser($member)->inspect('update', $tontine);

        $this->assertTrue($response->denied());
        $this->assertNull($response->status()); // pas de statut forcé : le framework répond 403
        $this->assertNotEmpty($response->message());
    }

    public function test_outsider_gets_a_404_for_every_ability(): void
    {
        $tontine = $this->tontine();
        $outsider = User::factory()->create();

        foreach (array_merge(self::MEMBER_ABILITIES, self::MANAGER_ABILITIES) as $ability) {
            $response = Gate::forUser($outsider)->inspect($ability, $tontine);

            $this->assertTrue($response->denied(), "L'étranger ne devrait pas avoir : {$ability}");
            $this->assertSame(404, $response->status(), "{$ability} devrait répondre 404 à un étranger");
        }
    }

    public function test_manager_role_is_scoped_to_one_tontine(): void
    {
        $tontineA = $this->tontine();
        $tontineB = $this->tontine();
        $tontineC = $this->tontine();
        $managerA = $this->managerOf($tontineA);

        // Le gérant de A est simple membre de B, et étranger à C.
        TontineMember::factory()->create(['tontine_id' => $tontineB->id, 'user_id' => $managerA->id]);

        $this->assertTrue($managerA->can('update', $tontineA));
        $this->assertTrue($managerA->can('view', $tontineB));
        $this->assertFalse($managerA->can('update', $tontineB));
        $this->assertFalse($managerA->can('draw', $tontineB));
        $this->assertSame(404, Gate::forUser($managerA)->inspect('view', $tontineC)->status());
    }

    public function test_inactive_member_loses_access(): void
    {
        $tontine = $this->tontine();
        $former = $this->addMember($tontine, ['status' => MemberStatus::Inactive]);

        $this->assertFalse($former->can('view', $tontine));
        $this->assertSame(404, Gate::forUser($former)->inspect('view', $tontine)->status());
    }

    public function test_inactive_manager_loses_access(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);

        TontineMember::where('tontine_id', $tontine->id)
            ->where('user_id', $manager->id)
            ->update(['status' => MemberStatus::Inactive->value]);

        $this->assertFalse($manager->can('update', $tontine));
        $this->assertFalse($manager->can('view', $tontine));
    }

    public function test_only_verified_users_can_create_a_tontine(): void
    {
        $verified = User::factory()->create();
        $unverified = User::factory()->unverified()->create();

        $this->assertTrue($verified->can('create', Tontine::class));
        $this->assertFalse($unverified->can('create', Tontine::class));
    }

    // ---------- Chaîne HTTP ----------

    public function test_http_member_gets_403_on_a_manager_action(): void
    {
        $this->registerProbes();
        $tontine = $this->tontine();
        $member = $this->addMember($tontine);

        $this->withToken($this->tokenFor($member))
            ->getJson('/api/v1/_probe/'.$tontine->public_id)
            ->assertForbidden();
    }

    public function test_http_outsider_gets_404(): void
    {
        $this->registerProbes();
        $tontine = $this->tontine();
        $outsider = User::factory()->create();

        $this->withToken($this->tokenFor($outsider))
            ->getJson('/api/v1/_probe/'.$tontine->public_id)
            ->assertNotFound();
    }

    public function test_http_manager_is_allowed(): void
    {
        $this->registerProbes();
        $tontine = $this->tontine();

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->getJson('/api/v1/_probe/'.$tontine->public_id)
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_http_requires_authentication(): void
    {
        $this->registerProbes();
        $tontine = $this->tontine();

        $this->getJson('/api/v1/_probe/'.$tontine->public_id)->assertUnauthorized();
    }

    public function test_http_internal_id_is_not_accepted_in_urls(): void
    {
        $this->registerProbes();
        $tontine = $this->tontine();

        // L'ID interne (ex. 1) ne doit jamais retrouver une tontine : seul le public_id fonctionne.
        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->getJson('/api/v1/_probe/'.$tontine->id)
            ->assertNotFound();
    }

    // ---------- Middleware "verified" ----------

    public function test_verified_middleware_blocks_unverified_users(): void
    {
        $this->registerProbes();
        $user = User::factory()->unverified()->create();

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/_probe-verified')
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');
    }

    public function test_verified_middleware_lets_verified_users_through(): void
    {
        $this->registerProbes();
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/_probe-verified')
            ->assertOk();
    }
}
