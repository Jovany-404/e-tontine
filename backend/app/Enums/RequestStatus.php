<?php

namespace App\Enums;

// Statut d'une demande d'adhésion.
enum RequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
