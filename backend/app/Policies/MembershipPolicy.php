<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Member;

class MemberPolicy
{
    public function view(User $user, Member $member): bool
    {
        return true; // Tous les admins peuvent voir les membres
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Member $member): bool
    {
        return true;
    }

    public function delete(User $user, Member $member): bool
    {
        return true;
    }
}
