<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'schedule_id',
        'amount',
        'method',
        'reference',
        'note',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'paid_at' => 'date',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ContributionSchedule::class, 'schedule_id');
    }

    /** Gérant qui a enregistré le paiement. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
