<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tontine;

class TontinePolicy
{
    public function view(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->user_id;
    }

    public function delete(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->user_id;
    }

    public function archive(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->user_id;
    }

    public function close(User $user, Tontine $tontine): bool
    {
        return $user->id === $tontine->user_id;
    }
}
