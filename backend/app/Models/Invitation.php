<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id',
        'created_by',
        'token_hash',
        'expires_at',
        'is_active',
    ];

    // Le hash n'est jamais renvoyé dans une réponse JSON.
    protected $hidden = [
        'token_hash',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Génère un token aléatoire sûr (à afficher une seule fois, jamais stocké en clair). */
    public static function generateToken(): string
    {
        return Str::random(48);
    }

    /** Hash stocké en base et utilisé pour retrouver l'invitation. */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** Le lien est utilisable s'il est actif et non expiré. */
    public function isUsable(): bool
    {
        return $this->is_active
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
