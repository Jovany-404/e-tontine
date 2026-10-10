<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

// Génère un identifiant public (ULID) et l'utilise pour la résolution des routes.
// L'API n'expose donc jamais les ID internes auto-incrémentés : pas d'énumération possible.
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }

    // /tontines/{tontine} cherchera la tontine par public_id.
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
