<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Écrit le journal d'activité : "qui a fait quoi, et quand".
 * Les lignes ne sont jamais modifiées. Ne jamais y mettre de données sensibles (mot de passe, token...).
 */
class ActivityLogger
{
    public function log(
        string $action,
        ?User $user,
        ?Tontine $tontine = null,
        ?Model $subject = null,
        array $metadata = [],
    ): ActivityLog {
        return ActivityLog::create([
            'tontine_id' => $tontine?->id,
            'user_id' => $user?->id,
            'action' => $action,
            // getMorphClass() renvoie l'alias court défini dans AppServiceProvider (ex. "tontine").
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
