<?php

namespace App\Models;

use App\Domains\StatusPage\Enums\IncidentImpact;
use App\Domains\StatusPage\Enums\IncidentStatus;
use Database\Factories\StatusIncidentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Incident (section 2.11) : un titre, un impact, une chronologie de mises à
 * jour (`StatusIncidentUpdate`) — voir `StatusIncidentService`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $title
 * @property IncidentStatus $status
 * @property IncidentImpact $impact
 * @property Carbon $started_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StatusIncident extends Model
{
    /** @use HasFactory<StatusIncidentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'title', 'status', 'impact', 'started_at', 'resolved_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'impact' => IncidentImpact::class,
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<StatusIncidentUpdate, $this> */
    public function updates(): HasMany
    {
        return $this->hasMany(StatusIncidentUpdate::class, 'incident_id');
    }
}
