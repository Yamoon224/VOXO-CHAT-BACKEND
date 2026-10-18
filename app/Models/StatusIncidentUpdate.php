<?php

namespace App\Models;

use App\Domains\StatusPage\Enums\IncidentStatus;
use Database\Factories\StatusIncidentUpdateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Une entrée de la chronologie publique d'un incident (section 2.11).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $incident_id
 * @property IncidentStatus $status
 * @property string $body
 * @property Carbon|null $created_at
 */
class StatusIncidentUpdate extends Model
{
    /** @use HasFactory<StatusIncidentUpdateFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'incident_id', 'status', 'body'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => IncidentStatus::class];
    }

    /** @return BelongsTo<StatusIncident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(StatusIncident::class, 'incident_id');
    }
}
