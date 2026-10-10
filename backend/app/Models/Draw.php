<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Draw extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'cycle_id',
        'tontine_id',
        'round',
        'winner_member_id',
        'amount',
        'drawn_at',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'amount' => 'integer',
            'drawn_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TontineMember::class, 'winner_member_id');
    }

    /** Gérant qui a lancé le tirage. */
    public function drawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }
}
