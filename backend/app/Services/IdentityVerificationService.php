<?php

namespace App\Services;

use App\Enums\IdentityStatus;
use App\Models\IdentityVerification;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class IdentityVerificationService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function submit(
        User $user,
        string $documentType,
        UploadedFile $front,
        ?UploadedFile $back,
        UploadedFile $profilePhoto,
    ): IdentityVerification {
        $storedPaths = [];
        $previous = $user->identityVerification()->first();

        try {
            $frontFile = $this->storeEncrypted($user, $front);
            $storedPaths[] = $frontFile['path'];

            $backFile = $back === null ? null : $this->storeEncrypted($user, $back);
            if ($backFile !== null) {
                $storedPaths[] = $backFile['path'];
            }

            $photoFile = $this->storeEncrypted($user, $profilePhoto);
            $storedPaths[] = $photoFile['path'];

            $verification = DB::transaction(function () use (
                $user,
                $documentType,
                $frontFile,
                $backFile,
                $photoFile,
            ): IdentityVerification {
                $verification = IdentityVerification::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'document_type' => $documentType,
                        'document_front_path' => $frontFile['path'],
                        'document_front_mime' => $frontFile['mime'],
                        'document_back_path' => $backFile['path'] ?? null,
                        'document_back_mime' => $backFile['mime'] ?? null,
                        'profile_photo_path' => $photoFile['path'],
                        'profile_photo_mime' => $photoFile['mime'],
                        'status' => IdentityStatus::Pending,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                        'submitted_at' => now(),
                    ],
                );

                $user->identity_status = IdentityStatus::Pending;
                $user->save();

                return $verification;
            });

            if ($previous !== null) {
                Storage::disk('local')->delete(array_filter([
                    $previous->document_front_path,
                    $previous->document_back_path,
                    $previous->profile_photo_path,
                ]));
            }

            return $verification;
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }
    }

    public function review(
        IdentityVerification $verification,
        User $reviewer,
        IdentityStatus $decision,
        Tontine $tontine,
        ?string $rejectionReason = null,
    ): IdentityVerification {
        return DB::transaction(function () use ($verification, $reviewer, $decision, $rejectionReason, $tontine) {
            $locked = IdentityVerification::query()
                ->whereKey($verification->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== IdentityStatus::Pending) {
                throw ValidationException::withMessages([
                    'identity' => [__('This identity submission has already been reviewed.')],
                ]);
            }

            $locked->status = $decision;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->rejection_reason = $decision === IdentityStatus::Rejected
                ? $rejectionReason
                : null;
            $locked->save();

            $user = $locked->user()->firstOrFail();
            $user->identity_status = $decision;
            $user->save();

            $this->activityLogger->log(
                $decision === IdentityStatus::Verified ? 'identity.verified' : 'identity.rejected',
                $reviewer,
                $tontine,
                $locked,
                [
                    'document_type' => $locked->document_type,
                    'membership_request_tontine_id' => $tontine->id,
                ],
            );

            return $locked;
        });
    }

    public function fileContents(IdentityVerification $verification, string $kind): array
    {
        $file = match ($kind) {
            'front' => [$verification->document_front_path, $verification->document_front_mime],
            'back' => [$verification->document_back_path, $verification->document_back_mime],
            'profile' => [$verification->profile_photo_path, $verification->profile_photo_mime],
            default => throw ValidationException::withMessages([
                'file' => [__('The requested identity file does not exist.')],
            ]),
        };

        if ($file[0] === null || $file[1] === null) {
            throw ValidationException::withMessages([
                'file' => [__('The requested identity file does not exist.')],
            ]);
        }

        $encrypted = Storage::disk('local')->get($file[0]);

        return [
            'contents' => Crypt::decryptString($encrypted),
            'mime' => $file[1],
        ];
    }

    private function storeEncrypted(User $user, UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('The uploaded identity file could not be read.');
        }

        $path = 'identity/'.$user->public_id.'/'.Str::uuid().'.enc';
        $saved = Storage::disk('local')->put($path, Crypt::encryptString($contents));

        if (! $saved) {
            throw new RuntimeException('The uploaded identity file could not be stored securely.');
        }

        return [
            'path' => $path,
            'mime' => $file->getMimeType(),
        ];
    }
}
