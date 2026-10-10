<?php

namespace App\Enums;

// Statut stocké d'une échéance. Le statut "En retard" n'est pas stocké : il est calculé.
enum ScheduleStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
}
