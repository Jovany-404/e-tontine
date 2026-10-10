<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
