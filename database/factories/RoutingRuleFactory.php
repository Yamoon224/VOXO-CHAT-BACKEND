<?php

namespace Database\Factories;

use App\Domains\Conversations\Enums\RoutingActionType;
use App\Models\RoutingRule;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RoutingRule> */
class RoutingRuleFactory extends Factory
{
    protected $model = RoutingRule::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Règle de test',
            'position' => 0,
            'is_active' => true,
            'conditions' => [],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => [],
            'next_round_robin_index' => 0,
        ];
    }
}
