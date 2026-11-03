<?php

namespace App\Models;

use App\Enums\IdentityStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerification extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_front_path',
        'document_front_mime',
        'document_back_path',
        'document_back_mime',
        'profile_photo_path',
        'profile_photo_mime',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'submitted_at',
    ];

    protected $hidden = [
        'document_front_path',
        'document_front_mime',
        'document_back_path',
        'document_back_mime',
        'profile_photo_path',
        'profile_photo_mime',
    ];

    protected function casts(): array
    {
        return [
            'status' => IdentityStatus::class,
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
