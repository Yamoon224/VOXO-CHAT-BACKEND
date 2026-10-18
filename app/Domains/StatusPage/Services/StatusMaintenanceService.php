<?php

namespace App\Domains\StatusPage\Services;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Contracts\StatusMaintenanceRepositoryContract;
use App\Models\StatusMaintenance;
use Illuminate\Support\Collection;

/** CRUD des maintenances planifiées (section 2.11). */
final class StatusMaintenanceService
{
    public function __construct(private readonly StatusMaintenanceRepositoryContract $maintenances) {}

    /** @return Collection<int, StatusMaintenance> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->maintenances->forWorkspace($scope->workspaceId);
    }

    /** @param  array{title: string, description: ?string, status: string, scheduled_start: string, scheduled_end: string}  $attributes */
    public function create(WorkspaceScope $scope, array $attributes): StatusMaintenance
    {
        return $this->maintenances->create([...$attributes, 'workspace_id' => $scope->workspaceId]);
    }

    /** @param  array{title: string, description: ?string, status: string, scheduled_start: string, scheduled_end: string}  $attributes */
    public function update(WorkspaceScope $scope, string $maintenanceId, array $attributes): StatusMaintenance
    {
        $maintenance = $this->maintenances->findInWorkspaceOrFail($scope->workspaceId, $maintenanceId);

        return $this->maintenances->update($maintenance, $attributes);
    }

    public function delete(WorkspaceScope $scope, string $maintenanceId): void
    {
        $this->maintenances->delete($this->maintenances->findInWorkspaceOrFail($scope->workspaceId, $maintenanceId));
    }
}
