<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionSchedule extends Model
{
    use HasFactory;

    // amount_paid, status et paid_at sont calculés par le serveur à partir des paiements.
    protected $fillable = [
        'cycle_id',
        'round',
        'tontine_member_id',
        'due_date',
        'amount_expected',
    ];

    protected $attributes = [
        'amount_paid' => 0,
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'due_date' => 'date',
            'amount_expected' => 'integer',
            'amount_paid' => 'integer',
            'status' => ScheduleStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /** En retard = échéance dépassée et non soldée. Calculé, jamais saisi à la main. */
    public function isOverdue(): bool
    {
        return $this->status !== ScheduleStatus::Paid
            && $this->due_date->lt(today());
    }

    /** Nombre de jours de retard (0 si l'échéance n'est pas en retard). */
    public function daysLate(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(today());
    }

    /** Montant restant à payer. */
    public function remainingAmount(): int
    {
        return max(0, $this->amount_expected - $this->amount_paid);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', ScheduleStatus::Paid->value)
            ->where('due_date', '<', today()->toDateString());
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TontineMember::class, 'tontine_member_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'schedule_id');
    }
}
