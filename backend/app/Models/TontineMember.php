<?php

namespace App\Models;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TontineMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected $attributes = [
        'role' => 'member',
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'status' => MemberStatus::class,
            'joined_at' => 'datetime',
        ];
    }

    public function isManager(): bool
    {
        return $this->role === MemberRole::Manager;
    }

    public function isActive(): bool
    {
        return $this->status === MemberStatus::Active;
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ContributionSchedule::class, 'tontine_member_id');
    }

    /** Tirages gagnés par ce membre. */
    public function wins(): HasMany
    {
        return $this->hasMany(Draw::class, 'winner_member_id');
    }
}
