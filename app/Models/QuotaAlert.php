<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Trace qu'un palier d'alerte de quota (80 %, 100 %) a déjà été notifié pour
 * un espace, afin de ne pas relancer le même e-mail à chaque passage de la
 * tâche planifiée (voir `QuotaAlertService`).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $quota_type
 * @property int $threshold
 * @property Carbon $sent_at
 */
class QuotaAlert extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'quota_type', 'threshold', 'sent_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
