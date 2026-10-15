<?php

namespace App\Domains\Automations\Repositories;

use App\Domains\Automations\Contracts\AutomationScenarioRepositoryContract;
use App\Domains\Automations\Enums\AutomationStatus;
use App\Models\AutomationScenario;
use Illuminate\Support\Collection;

final class EloquentAutomationScenarioRepository implements AutomationScenarioRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return AutomationScenario::query()
            ->where('workspace_id', $workspaceId)
            ->orderBy('position')
            ->get();
    }

    public function publishedForWorkspace(string $workspaceId): Collection
    {
        return AutomationScenario::query()
            ->where('workspace_id', $workspaceId)
            ->where('status', AutomationStatus::Published)
            ->orderBy('position')
            ->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): AutomationScenario
    {
        return AutomationScenario::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function create(array $attributes): AutomationScenario
    {
        return AutomationScenario::query()->create($attributes);
    }

    public function update(AutomationScenario $scenario, array $attributes): AutomationScenario
    {
        $scenario->update($attributes);

        return $scenario;
    }

    public function delete(AutomationScenario $scenario): void
    {
        $scenario->delete();
    }
}
