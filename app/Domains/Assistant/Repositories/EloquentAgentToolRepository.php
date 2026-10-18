<?php

namespace App\Domains\Assistant\Repositories;

use App\Domains\Assistant\Contracts\AgentToolRepositoryContract;
use App\Models\AgentTool;
use Illuminate\Support\Collection;

final class EloquentAgentToolRepository implements AgentToolRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return AgentTool::query()->where('workspace_id', $workspaceId)->latest('created_at')->get();
    }

    public function enabledForWorkspace(string $workspaceId): Collection
    {
        return AgentTool::query()->where('workspace_id', $workspaceId)->where('enabled', true)->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): AgentTool
    {
        return AgentTool::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function findByName(string $workspaceId, string $name): ?AgentTool
    {
        return AgentTool::query()->where('workspace_id', $workspaceId)->where('name', $name)->first();
    }

    public function create(array $attributes): AgentTool
    {
        return AgentTool::query()->create($attributes);
    }

    public function update(AgentTool $tool, array $attributes): AgentTool
    {
        $tool->update($attributes);

        return $tool;
    }

    public function delete(AgentTool $tool): void
    {
        $tool->delete();
    }
}
