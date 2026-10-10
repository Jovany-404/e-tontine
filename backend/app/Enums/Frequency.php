<?php

namespace App\Enums;

// Fréquence des cotisations (valeurs du MLD).
enum Frequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
}
