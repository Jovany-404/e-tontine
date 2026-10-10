<?php

namespace App\Enums;

enum TontineStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
}
