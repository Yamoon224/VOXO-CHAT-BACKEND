<?php

namespace App\Models;

use App\Domains\Conversations\Enums\RoutingActionType;
use Database\Factories\RoutingRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Règle de routage (section 2.4) : évaluée dans l'ordre de `position` à
 * chaque nouvelle conversation, la première qui correspond affecte.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property int $position
 * @property bool $is_active
 * @property list<array{field: string, operator: string, value: string}> $conditions
 * @property RoutingActionType $action_type
 * @property list<string> $assignee_user_ids
 * @property int $next_round_robin_index
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RoutingRule extends Model
{
    /** @use HasFactory<RoutingRuleFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'name', 'position', 'is_active', 'conditions',
        'action_type', 'assignee_user_ids', 'next_round_robin_index',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'conditions' => 'array',
            'action_type' => RoutingActionType::class,
            'assignee_user_ids' => 'array',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
