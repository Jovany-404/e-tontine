<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Enums\TontineStatus;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TontineTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/tontines';

    // ---------- Aides ----------

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tontine Famille',
            'description' => 'Cotisation mensuelle entre cousins',
            'type' => 'family',
            'contribution_amount' => 10000,
            'frequency' => 'monthly',
            'start_date' => now()->addWeek()->toDateString(),
            'expected_members' => 10,
        ], $overrides);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function tontine(array $attributes = []): Tontine
    {
        // La factory inscrit aussi le gérant comme membre (rôle manager).
        return Tontine::factory()->create($attributes);
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

    // ---------- Création ----------

    public function test_verified_user_can_create_a_tontine(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))
            ->postJson(self::URL, $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Tontine Famille')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.my_role', 'manager')
            ->assertJsonPath('data.members_count', 1)
            ->assertJsonPath('data.manager.name', $user->name)
            ->assertJsonStructure(['data' => [
                'id', 'name', 'type', 'contribution_amount', 'frequency',
                'start_date', 'expected_members', 'status',
            ]]);

        $tontine = Tontine::where('public_id', $response->json('data.id'))->firstOrFail();

        $this->assertEquals($user->id, $tontine->manager_id);

        // Le créateur est aussi membre actif, avec le rôle gérant.
        $this->assertDatabaseHas('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $user->id,
            'action' => 'tontine.created',
        ]);
    }

    public function test_unverified_user_cannot_create_a_tontine(): void
    {
        $user = User::factory()->unverified()->create();

        $this->withToken($this->tokenFor($user))
            ->postJson(self::URL, $this->payload())
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');

        $this->assertDatabaseCount('tontines', 0);
    }

    public function test_creation_requires_authentication(): void
    {
        $this->postJson(self::URL, $this->payload())->assertUnauthorized();
    }

    public function test_creator_cannot_force_manager_status_or_public_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))
            ->postJson(self::URL, $this->payload([
                'manager_id' => $other->id,
                'status' => 'active',
                'public_id' => 'FORCED',
            ]))
            ->assertCreated();

        $tontine = Tontine::where('public_id', $response->json('data.id'))->firstOrFail();

        $this->assertEquals($user->id, $tontine->manager_id);
        $this->assertSame(TontineStatus::Draft, $tontine->status);
        $this->assertNotSame('FORCED', $tontine->public_id);
    }

    public function test_creation_validates_every_field(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $cases = [
            'name' => ['name' => ''],
            'contribution_amount' => ['contribution_amount' => 100],
            'frequency' => ['frequency' => 'yearly'],
            'start_date' => ['start_date' => now()->subDay()->toDateString()],
            'expected_members' => ['expected_members' => 1],
            'type' => ['type' => 'bank'],
        ];

        foreach ($cases as $field => $override) {
            $this->withToken($token)
                ->postJson(self::URL, $this->payload($override))
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('tontines', 0);
    }

    public function test_creation_is_rate_limited(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        for ($i = 0; $i < 10; $i++) {
            $this->withToken($token)->postJson(self::URL, $this->payload())->assertCreated();
        }

        $this->withToken($token)->postJson(self::URL, $this->payload())->assertStatus(429);
    }

    // ---------- Liste et détail ----------

    public function test_index_lists_only_my_active_tontines(): void
    {
        $user = User::factory()->create();

        $own = $this->tontine(['manager_id' => $user->id]);

        $joined = $this->tontine();
        TontineMember::factory()->create(['tontine_id' => $joined->id, 'user_id' => $user->id]);

        $foreign = $this->tontine();

        $left = $this->tontine();
        TontineMember::factory()->create([
            'tontine_id' => $left->id,
            'user_id' => $user->id,
            'status' => MemberStatus::Inactive,
        ]);

        $response = $this->withToken($this->tokenFor($user))
            ->getJson(self::URL)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $roles = collect($response->json('data'))->pluck('my_role', 'id');

        $this->assertEquals('manager', $roles[$own->public_id]);
        $this->assertEquals('member', $roles[$joined->public_id]);
        $this->assertArrayNotHasKey($foreign->public_id, $roles->all());
        $this->assertArrayNotHasKey($left->public_id, $roles->all());
    }

    public function test_manager_and_member_can_view_the_tontine(): void
    {
        $tontine = $this->tontine();
        $member = $this->addMember($tontine);

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->getJson(self::URL.'/'.$tontine->public_id)
            ->assertOk()
            ->assertJsonPath('data.id', $tontine->public_id)
            ->assertJsonPath('data.my_role', 'manager')
            ->assertJsonPath('data.members_count', 2);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::URL.'/'.$tontine->public_id)
            ->assertOk()
            ->assertJsonPath('data.my_role', 'member');
    }

    public function test_detail_does_not_expose_sensitive_data(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $member = $this->addMember($tontine);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::URL.'/'.$tontine->public_id)
            ->assertOk()
            ->assertJsonPath('data.manager.id', $manager->public_id)
            ->assertJsonPath('data.manager.name', $manager->name)
            ->assertJsonMissingPath('data.manager.email')
            ->assertJsonMissingPath('data.manager.phone')
            ->assertJsonMissingPath('data.manager_id');
    }

    public function test_outsider_gets_the_same_404_as_a_missing_tontine(): void
    {
        $tontine = $this->tontine();
        $token = $this->tokenFor(User::factory()->create());

        $forbidden = $this->withToken($token)->getJson(self::URL.'/'.$tontine->public_id);
        $missing = $this->withToken($token)->getJson(self::URL.'/01JZZZZZZZZZZZZZZZZZZZZZZZ');

        $forbidden->assertNotFound();
        $missing->assertNotFound();

        // Corps identique : impossible de savoir si la tontine existe.
        $this->assertSame($missing->json(), $forbidden->json());
    }

    public function test_internal_id_is_not_accepted_in_urls(): void
    {
        $tontine = $this->tontine();

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->getJson(self::URL.'/'.$tontine->id)
            ->assertNotFound();
    }

    // ---------- Modification ----------

    public function test_manager_can_update_a_draft_tontine(): void
    {
        $tontine = $this->tontine(['contribution_amount' => 10000]);

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->patchJson(self::URL.'/'.$tontine->public_id, [
                'name' => 'Nouveau nom',
                'contribution_amount' => 20000,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau nom')
            ->assertJsonPath('data.contribution_amount', 20000);

        $this->assertDatabaseHas('tontines', [
            'id' => $tontine->id,
            'name' => 'Nouveau nom',
            'contribution_amount' => 20000,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'action' => 'tontine.updated',
        ]);
    }

    public function test_member_cannot_update_the_tontine(): void
    {
        $tontine = $this->tontine(['name' => 'Original']);
        $member = $this->addMember($tontine);

        $this->withToken($this->tokenFor($member))
            ->patchJson(self::URL.'/'.$tontine->public_id, ['name' => 'Piraté'])
            ->assertForbidden();

        $this->assertDatabaseHas('tontines', ['id' => $tontine->id, 'name' => 'Original']);
    }

    public function test_outsider_gets_404_before_validation_on_update(): void
    {
        $tontine = $this->tontine();
        $token = $this->tokenFor(User::factory()->create());

        // Données invalides : l'étranger doit recevoir 404 et non 422, sinon l'existence de la tontine fuiterait.
        $this->withToken($token)
            ->patchJson(self::URL.'/'.$tontine->public_id, ['contribution_amount' => 0])
            ->assertNotFound();
    }

    public function test_manager_of_another_tontine_cannot_update_this_one(): void
    {
        $tontineA = $this->tontine();
        $tontineB = $this->tontine(['name' => 'Original']);

        $this->withToken($this->tokenFor($this->managerOf($tontineA)))
            ->patchJson(self::URL.'/'.$tontineB->public_id, ['name' => 'Piraté'])
            ->assertNotFound();

        $this->assertDatabaseHas('tontines', ['id' => $tontineB->id, 'name' => 'Original']);
    }

    public function test_started_tontine_locks_financial_fields(): void
    {
        $tontine = Tontine::factory()->active()->create(['contribution_amount' => 10000]);
        $token = $this->tokenFor($this->managerOf($tontine));
        $url = self::URL.'/'.$tontine->public_id;

        // Changer le montant est refusé.
        $this->withToken($token)
            ->patchJson($url, ['contribution_amount' => 20000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contribution_amount');

        $this->assertDatabaseHas('tontines', ['id' => $tontine->id, 'contribution_amount' => 10000]);

        // Changer le nom reste possible.
        $this->withToken($token)
            ->patchJson($url, ['name' => 'Renommée'])
            ->assertOk();

        // Renvoyer les mêmes valeurs financières (formulaire complet) est accepté.
        $this->withToken($token)
            ->patchJson($url, [
                'name' => 'Renommée encore',
                'contribution_amount' => 10000,
                'frequency' => 'monthly',
                'start_date' => $tontine->start_date->toDateString(),
                'expected_members' => $tontine->expected_members,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renommée encore');
    }

    public function test_expected_members_cannot_drop_below_current_members(): void
    {
        $tontine = $this->tontine(['expected_members' => 10]);
        $this->addMember($tontine);
        $this->addMember($tontine);

        $token = $this->tokenFor($this->managerOf($tontine));
        $url = self::URL.'/'.$tontine->public_id;

        // Gérant + 2 membres = 3 membres actifs.
        $this->withToken($token)
            ->patchJson($url, ['expected_members' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_members');

        $this->withToken($token)
            ->patchJson($url, ['expected_members' => 3])
            ->assertOk()
            ->assertJsonPath('data.expected_members', 3);
    }

    public function test_update_ignores_manager_and_status(): void
    {
        $tontine = $this->tontine();
        $manager = $this->managerOf($tontine);
        $other = User::factory()->create();

        $this->withToken($this->tokenFor($manager))
            ->patchJson(self::URL.'/'.$tontine->public_id, [
                'name' => 'Modifiée',
                'manager_id' => $other->id,
                'status' => 'completed',
            ])
            ->assertOk();

        $fresh = $tontine->fresh();

        $this->assertEquals($manager->id, $fresh->manager_id);
        $this->assertSame(TontineStatus::Draft, $fresh->status);
        $this->assertSame('Modifiée', $fresh->name);
    }

    public function test_no_log_is_written_when_nothing_changes(): void
    {
        $tontine = $this->tontine(['name' => 'Identique']);

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->patchJson(self::URL.'/'.$tontine->public_id, ['name' => 'Identique'])
            ->assertOk();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'tontine.updated']);
    }

    public function test_draft_start_date_cannot_move_to_the_past(): void
    {
        $tontine = $this->tontine();

        $this->withToken($this->tokenFor($this->managerOf($tontine)))
            ->patchJson(self::URL.'/'.$tontine->public_id, [
                'start_date' => now()->subDay()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_date');
    }
}
