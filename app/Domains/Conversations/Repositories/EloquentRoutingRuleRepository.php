<?php

namespace App\Domains\Conversations\Repositories;

use App\Domains\Conversations\Contracts\RoutingRuleRepositoryContract;
use App\Models\RoutingRule;
use Illuminate\Support\Collection;

final class EloquentRoutingRuleRepository implements RoutingRuleRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return RoutingRule::query()->where('workspace_id', $workspaceId)->orderBy('position')->get();
    }

    public function activeForWorkspace(string $workspaceId): Collection
    {
        return RoutingRule::query()
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->orderBy('position')
            ->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): RoutingRule
    {
        return RoutingRule::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function create(array $attributes): RoutingRule
    {
        return RoutingRule::create($attributes);
    }

    public function update(RoutingRule $rule, array $attributes): RoutingRule
    {
        $rule->update($attributes);

        return $rule;
    }

    public function delete(RoutingRule $rule): void
    {
        $rule->delete();
    }
}
