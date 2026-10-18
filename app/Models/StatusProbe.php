<?php

namespace App\Models;

use App\Domains\StatusPage\Enums\ProbeStatus;
use Database\Factories\StatusProbeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Sonde de disponibilité (section 2.11) : une URL vérifiée périodiquement —
 * voir `CheckStatusProbesCommand`/`CheckStatusProbeJob`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property string $url
 * @property int $check_interval_minutes
 * @property ProbeStatus $status
 * @property Carbon|null $last_checked_at
 * @property int $consecutive_failures
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StatusProbe extends Model
{
    /** @use HasFactory<StatusProbeFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'url', 'check_interval_minutes', 'status', 'last_checked_at', 'consecutive_failures'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => ProbeStatus::class, 'last_checked_at' => 'datetime'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<StatusProbeCheck, $this> */
    public function checks(): HasMany
    {
        return $this->hasMany(StatusProbeCheck::class, 'probe_id');
    }

    public function isDueForCheck(Carbon $now): bool
    {
        return $this->last_checked_at === null || $this->last_checked_at->addMinutes($this->check_interval_minutes)->lte($now);
    }
}
