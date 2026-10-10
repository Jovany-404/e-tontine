<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContributionCycle extends Model
{
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];
}
