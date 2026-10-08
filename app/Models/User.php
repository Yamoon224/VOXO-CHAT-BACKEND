<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Compte utilisateur d'une équipe cliente ou de la plateforme.
 *
 * Un compte n'appartient à aucun espace de travail en propre : il en rejoint
 * un ou plusieurs par ses adhésions (`WorkspaceMember`), qui portent son rôle
 * dans chacun. Le seul rôle attribué au compte lui-même est `platform_admin`.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $locale
 * @property bool $is_active
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids;

    public const PLATFORM_ADMIN_ROLE = 'platform_admin';

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'password', 'locale', 'is_active'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            // Chiffré au repos : une fuite de base ne suffit pas à rejouer les
            // codes d'un compte.
            'two_factor_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<WorkspaceMember, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }
}
