<?php

namespace App\Domains\StatusPage\Repositories;

use App\Domains\StatusPage\Contracts\StatusMaintenanceRepositoryContract;
use App\Domains\StatusPage\Enums\MaintenanceStatus;
use App\Models\StatusMaintenance;
use Illuminate\Support\Collection;

final class EloquentStatusMaintenanceRepository implements StatusMaintenanceRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return StatusMaintenance::query()->where('workspace_id', $workspaceId)->latest('scheduled_start')->get();
    }

    public function upcomingForWorkspace(string $workspaceId): Collection
    {
        return StatusMaintenance::query()
            ->where('workspace_id', $workspaceId)
            ->where('status', '!=', MaintenanceStatus::Completed->value)
            ->oldest('scheduled_start')
            ->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): StatusMaintenance
    {
        return StatusMaintenance::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function create(array $attributes): StatusMaintenance
    {
        return StatusMaintenance::query()->create($attributes);
    }

    public function update(StatusMaintenance $maintenance, array $attributes): StatusMaintenance
    {
        $maintenance->update($attributes);

        return $maintenance;
    }

    public function delete(StatusMaintenance $maintenance): void
    {
        $maintenance->delete();
    }
}
