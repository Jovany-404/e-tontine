<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    // Pas de colonne updated_at : une ligne de journal n'est jamais modifiée.
    const UPDATED_AT = null;

    protected $fillable = [
        'tontine_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Objet concerné par l'action (paiement, demande d'adhésion, etc.). */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
