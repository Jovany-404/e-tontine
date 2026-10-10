<?php

namespace App\Services;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\TontineStatus;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TontineService
{
    private const LOCKED_FIELDS = [
        'contribution_amount',
        'frequency',
        'start_date',
        'expected_members',
    ];

    public function __construct(private ActivityLogger $activityLogger) {}

    public function create(User $manager, array $attributes): Tontine
    {
        return DB::transaction(function () use ($manager, $attributes): Tontine {
            $tontine = new Tontine;
            $tontine->manager_id = $manager->id;
            $tontine->status = TontineStatus::Draft;
            $tontine->fill($attributes);
            $tontine->save();

            $tontine->members()->create([
                'user_id' => $manager->id,
                'role' => MemberRole::Manager,
                'status' => MemberStatus::Active,
                'joined_at' => now(),
            ]);

            $this->activityLogger->log('tontine.created', $manager, $tontine);

            return $tontine;
        });
    }

    public function update(Tontine $tontine, User $manager, array $attributes): Tontine
    {
        return DB::transaction(function () use ($tontine, $manager, $attributes): Tontine {
            $tontine = Tontine::query()
                ->whereKey($tontine->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $errors = [];

            if ($tontine->status !== TontineStatus::Draft) {
                foreach (self::LOCKED_FIELDS as $field) {
                    if (array_key_exists($field, $attributes)
                        && $this->normalize($field, $attributes[$field]) !== $this->normalize($field, $tontine->{$field})) {
                        $errors[$field] = 'Ce champ ne peut plus être modifié après le démarrage de la tontine.';
                    }
                }
            } elseif (array_key_exists('start_date', $attributes)
                && $attributes['start_date'] < today()->toDateString()) {
                $errors['start_date'] = 'La date de début ne peut pas être dans le passé.';
            }

            if (array_key_exists('expected_members', $attributes)
                && (int) $attributes['expected_members'] < $tontine->activeMembers()->count()) {
                $errors['expected_members'] = 'Le nombre de membres prévu ne peut pas être inférieur au nombre de membres actifs.';
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $tontine->fill($attributes);

            if ($tontine->isDirty()) {
                $tontine->save();
                $this->activityLogger->log('tontine.updated', $manager, $tontine);
            }

            return $tontine;
        });
    }

    public function roleOf(User $user, Tontine $tontine): ?MemberRole
    {
        return $tontine->members()
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::Active->value)
            ->first()
            ?->role;
    }

    private function normalize(string $field, mixed $value): mixed
    {
        return match ($field) {
            'contribution_amount', 'expected_members' => (int) $value,
            'start_date' => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d')
                : (string) $value,
            default => $value instanceof \BackedEnum ? $value->value : $value,
        };
    }
}
