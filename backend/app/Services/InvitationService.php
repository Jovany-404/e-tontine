<?php

namespace App\Services;

use App\Enums\TontineStatus;
use App\Models\Invitation;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    // Durée de validité d'un lien si le gérant n'en choisit pas.
    public const DEFAULT_DAYS = 7;

    public function __construct(private ActivityLogger $logger)
    {
    }

    /**
     * Génère un nouveau lien et désactive l'ancien.
     * Le token en clair est renvoyé UNE SEULE FOIS : seule son empreinte (hash) est stockée.
     *
     * @return array{0: Invitation, 1: string} [invitation, token en clair]
     */
    public function generate(Tontine $tontine, User $actor, ?int $days): array
    {
        if ($tontine->status === TontineStatus::Completed) {
            throw ValidationException::withMessages([
                'tontine' => [__('This tontine is completed: no invitation can be created.')],
            ]);
        }

        return DB::transaction(function () use ($tontine, $actor, $days) {
            // Un seul lien actif à la fois : régénérer révoque l'ancien.
            $tontine->invitations()->where('is_active', true)->update(['is_active' => false]);

            $token = Invitation::generateToken();

            $invitation = $tontine->invitations()->create([
                'created_by' => $actor->id,
                'token_hash' => Invitation::hashToken($token),
                'expires_at' => $days === null ? null : now()->addDays($days),
                'is_active' => true,
            ]);

            // Le token n'est volontairement jamais écrit dans le journal.
            $this->logger->log('invitation.created', $actor, $tontine, $invitation, [
                'expires_at' => $invitation->expires_at?->toIso8601String(),
            ]);

            return [$invitation, $token];
        });
    }

    /** Désactive tous les liens actifs. Renvoie le nombre de liens désactivés. */
    public function disable(Tontine $tontine, User $actor): int
    {
        return DB::transaction(function () use ($tontine, $actor) {
            $count = $tontine->invitations()->where('is_active', true)->update(['is_active' => false]);

            if ($count > 0) {
                $this->logger->log('invitation.disabled', $actor, $tontine, $tontine);
            }

            return $count;
        });
    }

    /** Lien actuellement utilisable de la tontine (null s'il n'y en a pas). */
    public function current(Tontine $tontine): ?Invitation
    {
        return $tontine->invitations()->usable()->latest('id')->first();
    }

    /** Retrouve une invitation utilisable (active, non expirée) à partir du token en clair. */
    public function findUsable(string $token): ?Invitation
    {
        return Invitation::usable()
            ->where('token_hash', Invitation::hashToken($token))
            ->with('tontine.manager:id,public_id,name')
            ->first();
    }

    /** Lien complet à partager, qui pointe vers la page React. */
    public function url(string $token): string
    {
        return config('etontine.frontend_url').'/join/'.$token;
    }
}
