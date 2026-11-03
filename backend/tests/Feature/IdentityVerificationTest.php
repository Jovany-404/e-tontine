<?php

namespace Tests\Feature;

use App\Enums\IdentityStatus;
use App\Models\IdentityVerification;
use App\Models\MembershipRequest;
use App\Models\Tontine;
use App\Models\User;
use App\Services\IdentityVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function png(string $name): UploadedFile
    {
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a1d8AAAAASUVORK5CYII=',
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    private function submission(string $type = 'national_id'): array
    {
        return [
            'document_type' => $type,
            'document_front' => $this->png('document-front.png'),
            'document_back' => $type === 'national_id' ? $this->png('document-back.png') : null,
            'profile_photo' => $this->png('profile.png'),
            'identity_consent' => '1',
        ];
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function submitFor(User $user, string $type = 'national_id'): IdentityVerification
    {
        $files = $this->submission($type);

        return app(IdentityVerificationService::class)->submit(
            $user,
            $files['document_type'],
            $files['document_front'],
            $files['document_back'],
            $files['profile_photo'],
        );
    }

    private function reviewUrl(Tontine $tontine, MembershipRequest $membershipRequest): string
    {
        return '/api/v1/tontines/'.$tontine->public_id
            .'/membership-requests/'.$membershipRequest->public_id.'/identity';
    }

    public function test_registration_requires_an_identity_document_and_profile_photo(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/auth/register', [
            'name' => 'Awa Diop',
            'email' => 'awa@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'document_type',
                'document_front',
                'profile_photo',
                'identity_consent',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_national_id_upload_is_encrypted_and_account_starts_pending(): void
    {
        $payload = array_merge($this->submission(), [
            'name' => 'Awa Diop',
            'email' => 'awa@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'identity_status' => 'verified',
        ]);
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/auth/register', $payload)
            ->assertCreated()
            ->assertJsonPath('user.identity_status', 'pending')
            ->assertJsonMissingPath('user.document_front_path')
            ->assertJsonMissingPath('user.profile_photo_path');

        $user = User::where('email', 'awa@example.com')->firstOrFail();
        $verification = $user->identityVerification()->firstOrFail();
        $this->assertSame(IdentityStatus::Pending, $user->identity_status);
        $this->assertSame(IdentityStatus::Pending, $verification->status);
        Storage::disk('local')->assertExists($verification->document_front_path);
        $this->assertStringNotContainsString(
            'iVBORw0KGgo',
            Storage::disk('local')->get($verification->document_front_path),
        );

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/identity')
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.document_type', 'national_id')
            ->assertJsonMissingPath('data.document_front_path');
    }

    public function test_passport_does_not_accept_a_card_back_and_card_requires_both_sides(): void
    {
        $payload = array_merge($this->submission('passport'), [
            'name' => 'Moussa Diop',
            'email' => 'moussa@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/auth/register', $payload)
            ->assertCreated();

        $cardPayload = array_merge($this->submission('national_id'), [
            'name' => 'Carte Test',
            'email' => 'card@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'document_back' => null,
        ]);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/auth/register', $cardPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document_back');

        $passportWithBack = array_merge($payload, [
            'email' => 'passport-with-back@example.com',
            'document_back' => $this->png('unexpected-back.png'),
        ]);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/auth/register', $passportWithBack)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document_back');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_identity_pending_does_not_lock_the_applicant_out_of_the_platform(): void
    {
        $user = User::factory()->create(['identity_status' => IdentityStatus::Pending]);
        $this->submitFor($user);

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/tontines')
            ->assertOk();
    }

    public function test_only_the_requested_tontine_manager_can_view_and_verify_identity_documents(): void
    {
        $tontine = Tontine::factory()->create();
        $manager = User::findOrFail($tontine->manager_id);
        $applicant = User::factory()->create(['identity_status' => IdentityStatus::Pending]);
        $verification = $this->submitFor($applicant);
        $membershipRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $applicant->id,
        ]);
        $anotherManager = User::factory()->create();

        $this->withToken($this->tokenFor($anotherManager))
            ->get('/api/v1/tontines/'.$tontine->public_id.'/membership-requests/'.$membershipRequest->public_id.'/identity/files/front')
            ->assertNotFound();

        $managerToken = $this->tokenFor($manager);
        $this->withToken($managerToken)
            ->getJson('/api/v1/tontines/'.$tontine->public_id.'/membership-requests')
            ->assertOk()
            ->assertJsonPath('data.0.user.identity_status', 'pending')
            ->assertJsonMissingPath('data.0.user.email')
            ->assertJsonMissingPath('data.0.document_front_path');

        $this->withToken($managerToken)
            ->get($this->reviewUrl($tontine, $membershipRequest).'/files/front')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->withToken($managerToken)
            ->postJson($this->reviewUrl($tontine, $membershipRequest).'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonMissingPath('data.user.email');

        $this->assertSame(IdentityStatus::Verified, $applicant->fresh()->identity_status);
        $this->assertDatabaseHas('activity_logs', [
            'tontine_id' => $tontine->id,
            'user_id' => $manager->id,
            'action' => 'identity.verified',
            'subject_type' => 'identity_verification',
            'subject_id' => $verification->id,
        ]);

        $this->withToken($managerToken)
            ->postJson($this->reviewUrl($tontine, $membershipRequest).'/approve')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);

        $this->withToken($this->tokenFor($manager))
            ->get($this->reviewUrl($tontine, $membershipRequest).'/files/front')
            ->assertNotFound();
    }

    public function test_manager_can_reject_documents_and_user_can_resubmit_afterward(): void
    {
        $tontine = Tontine::factory()->create();
        $manager = User::findOrFail($tontine->manager_id);
        $applicant = User::factory()->create(['identity_status' => IdentityStatus::Pending]);
        $verification = $this->submitFor($applicant, 'passport');
        $membershipRequest = MembershipRequest::factory()->create([
            'tontine_id' => $tontine->id,
            'user_id' => $applicant->id,
        ]);

        $this->withToken($this->tokenFor($manager))
            ->postJson($this->reviewUrl($tontine, $membershipRequest).'/reject', [
                'reason' => 'La photo du passeport est illisible.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'La photo du passeport est illisible.');

        $this->assertSame(IdentityStatus::Rejected, $applicant->fresh()->identity_status);

        $this->withToken($this->tokenFor($applicant))
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/identity/submissions', $this->submission('passport'))
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.rejection_reason', null);

        $this->assertSame(IdentityStatus::Pending, $applicant->fresh()->identity_status);
        Storage::disk('local')->assertMissing($verification->document_front_path);
    }

    public function test_profile_photo_is_available_only_to_its_owner(): void
    {
        $owner = User::factory()->create(['identity_status' => IdentityStatus::Pending]);
        $this->submitFor($owner);
        $otherUser = User::factory()->create();

        $this->withToken($this->tokenFor($owner))
            ->get('/api/v1/identity/profile-photo')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox");

        $this->withToken($this->tokenFor($otherUser))
            ->getJson('/api/v1/identity/profile-photo')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found.']);
    }

    public function test_user_resource_reports_profile_photo_availability_without_exposing_file_paths(): void
    {
        $user = User::factory()->create(['identity_status' => IdentityStatus::Unverified]);
        $token = $this->tokenFor($user);

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.profile_photo_available', false);

        $this->submitFor($user);

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.profile_photo_available', true)
            ->assertJsonMissingPath('data.profile_photo_path')
            ->assertJsonMissingPath('data.document_front_path');
    }
}
