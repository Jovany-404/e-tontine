<?php

namespace App\Models;

use App\Enums\CycleStatus;
use App\Enums\Frequency;
use App\Enums\MemberStatus;
use App\Enums\TontineStatus;
use App\Enums\TontineType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tontine extends Model
{
    use HasFactory, HasPublicId;

    // manager_id et status sont absents : ils sont fixés par le serveur, jamais par la requête.
    protected $fillable = [
        'name',
        'description',
        'type',
        'contribution_amount',
        'frequency',
        'start_date',
        'expected_members',
        'rules',
    ];

    protected $attributes = [
        'type' => 'other',
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'type' => TontineType::class,
            'frequency' => Frequency::class,
            'status' => TontineStatus::class,
            'start_date' => 'date',
            'contribution_amount' => 'integer',
            'expected_members' => 'integer',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TontineMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(TontineMember::class)->where('status', MemberStatus::Active->value);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function membershipRequests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(ContributionSchedule::class, Cycle::class);
    }

    /** Cycle en cours (au plus un par tontine). */
    public function currentCycle(): HasOne
    {
        return $this->hasOne(Cycle::class)->where('status', CycleStatus::Open->value);
    }

    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class);
    }
}
