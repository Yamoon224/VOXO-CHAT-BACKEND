<?php

namespace App\Models;

use App\Domains\Automations\Enums\AutomationActionType;
use App\Domains\Automations\Enums\AutomationStatus;
use App\Domains\Automations\Enums\AutomationTriggerType;
use Database\Factories\AutomationScenarioFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Scénario d'automatisation (section 2.8) : un déclencheur, des conditions,
 * une suite d'étapes exécutées dans l'ordre — voir `AutomationExecutionService`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property int $position
 * @property AutomationStatus $status
 * @property AutomationTriggerType $trigger_type
 * @property list<array{field: string, operator: string, value: string}> $conditions
 * @property list<array{type: string, config: array<string, mixed>}> $steps
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AutomationScenario extends Model
{
    /** @use HasFactory<AutomationScenarioFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'position', 'status', 'trigger_type', 'conditions', 'steps'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AutomationStatus::class,
            'trigger_type' => AutomationTriggerType::class,
            'conditions' => 'array',
            'steps' => 'array',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function hasAskQuestionStepAt(int $index): bool
    {
        $step = $this->steps[$index] ?? null;

        return $step !== null && $step['type'] === AutomationActionType::AskQuestion->value;
    }
}
