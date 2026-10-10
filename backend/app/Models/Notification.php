<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $casts = [
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
