<?php

namespace App\Models;

use App\Domains\Shared\Enums\WorkspaceRole;
use Database\Factories\WorkspaceMemberFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Adhésion d'un compte à un espace de travail, avec le rôle qu'il y tient.
 *
 * C'est la seule source de vérité du rôle : la matrice des permissions, elle,
 * vit dans les rôles semés par `RolesAndPermissionsSeeder`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $user_id
 * @property WorkspaceRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read User $user
 */
class WorkspaceMember extends Model
{
    /** @use HasFactory<WorkspaceMemberFactory> */
    use HasFactory, HasUuids, LogsActivity;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'user_id', 'role'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['role' => WorkspaceRole::class];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === WorkspaceRole::Owner;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['workspace_id', 'user_id', 'role'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
