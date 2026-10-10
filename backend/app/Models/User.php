<?php

namespace App\Models;

use App\Enums\IdentityStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// MustVerifyEmail : active l'envoi et le contrôle de la vérification de l'adresse email.
class User extends Authenticatable implements MustVerifyEmail
{
    // HasApiTokens : tokens Bearer Sanctum. HasPublicId : identifiant public ULID.
    use HasApiTokens, HasFactory, HasPublicId, Notifiable;

    // Seuls ces champs peuvent être remplis par assignation de masse.
    // Les statuts de vérification et d'identité ne sont jamais modifiables par une requête.
    protected $fillable = [
        'name',
        'email',
        'phone',
        'city',
        'password',
    ];

    // Ces champs ne sont jamais renvoyés dans une réponse JSON.
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    // Valeur par défaut identique à celle de la base (nécessaire avec le mode strict).
    protected $attributes = [
        'identity_status' => 'unverified',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'identity_status' => IdentityStatus::class,
            'password' => 'hashed',
        ];
    }

    /** Tontines gérées par cet utilisateur. */
    public function managedTontines(): HasMany
    {
        return $this->hasMany(Tontine::class, 'manager_id');
    }

    /** Appartenances (une ligne par tontine, avec le rôle dans cette tontine). */
    public function memberships(): HasMany
    {
        return $this->hasMany(TontineMember::class);
    }

    /** Toutes les tontines auxquelles l'utilisateur participe. */
    public function tontines(): BelongsToMany
    {
        return $this->belongsToMany(Tontine::class, 'tontine_members')
            ->withPivot('role', 'status', 'joined_at')
            ->withTimestamps();
    }

    public function membershipRequests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class);
    }
}
