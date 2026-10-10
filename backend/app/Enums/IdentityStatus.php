<?php

namespace App\Enums;

// Statut de vérification d'identité (activé en V2, colonne prévue dès la V1).
enum IdentityStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
