<?php

namespace App\Enums;

// Rôle d'un utilisateur DANS une tontine (et non un rôle global).
enum MemberRole: string
{
    case Manager = 'manager';
    case Member = 'member';
}
