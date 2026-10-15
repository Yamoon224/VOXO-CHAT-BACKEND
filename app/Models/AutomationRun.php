<?php

namespace App\Models;

use App\Domains\Automations\Enums\AutomationRunStatus;
use Database\Factories\AutomationRunFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Exécution en cours (ou terminée) d'un scénario pour une conversation
 * (section 2.8) — voir `AutomationExecutionService`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $conversation_id
 * @property string $scenario_id
 * @property int $current_step
 * @property AutomationRunStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AutomationRun extends Model
{
    /** @use HasFactory<AutomationRunFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'conversation_id', 'scenario_id', 'current_step', 'status'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => AutomationRunStatus::class];
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<AutomationScenario, $this> */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(AutomationScenario::class);
    }
}
