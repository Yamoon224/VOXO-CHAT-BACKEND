<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Espace de travail : le tenant. Toute donnée métier lui est rattachée.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $locale
 * @property string $timezone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuids, LogsActivity;

    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'locale', 'timezone'];

    /** @return HasMany<WorkspaceMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /** @return HasMany<WorkspaceInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /** @return HasOne<Subscription, $this> */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'locale', 'timezone'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
