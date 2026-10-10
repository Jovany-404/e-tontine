<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contribution extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_paiement' => 'date',
        'date_echeance' => 'date',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
