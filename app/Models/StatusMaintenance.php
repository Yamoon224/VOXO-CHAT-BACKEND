<?php

namespace App\Models;

use App\Domains\StatusPage\Enums\MaintenanceStatus;
use Database\Factories\StatusMaintenanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maintenance planifiée (section 2.11).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $title
 * @property string|null $description
 * @property MaintenanceStatus $status
 * @property Carbon $scheduled_start
 * @property Carbon $scheduled_end
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StatusMaintenance extends Model
{
    /** @use HasFactory<StatusMaintenanceFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'title', 'description', 'status', 'scheduled_start', 'scheduled_end'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => MaintenanceStatus::class, 'scheduled_start' => 'datetime', 'scheduled_end' => 'datetime'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
