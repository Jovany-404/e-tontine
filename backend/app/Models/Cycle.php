<?php

namespace App\Models;

use App\Enums\CycleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id',
        'number',
        'started_at',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'status' => CycleStatus::class,
            'number' => 'integer',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ContributionSchedule::class);
    }

    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class);
    }
}
