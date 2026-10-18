<?php

namespace App\Models;

use App\Domains\StatusPage\Enums\ProbeStatus;
use Database\Factories\StatusProbeCheckFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Trace d'une vérification de sonde (section 2.11) — append-only, pour le
 * taux de disponibilité affiché sur la page publique.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $probe_id
 * @property ProbeStatus $status
 * @property int|null $response_time_ms
 * @property Carbon $checked_at
 * @property Carbon|null $created_at
 */
class StatusProbeCheck extends Model
{
    /** @use HasFactory<StatusProbeCheckFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'probe_id', 'status', 'response_time_ms', 'checked_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => ProbeStatus::class, 'checked_at' => 'datetime'];
    }

    /** @return BelongsTo<StatusProbe, $this> */
    public function probe(): BelongsTo
    {
        return $this->belongsTo(StatusProbe::class, 'probe_id');
    }
}
