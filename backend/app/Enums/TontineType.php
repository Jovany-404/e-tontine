<?php

namespace App\Enums;

// Remplace la table "Type" du diagramme de classes (simplifiée en V1).
enum TontineType: string
{
    case Family = 'family';
    case Friends = 'friends';
    case Work = 'work';
    case Other = 'other';
}
