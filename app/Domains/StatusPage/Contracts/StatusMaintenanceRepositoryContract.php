<?php

namespace App\Domains\StatusPage\Contracts;

use App\Models\StatusMaintenance;
use Illuminate\Support\Collection;

interface StatusMaintenanceRepositoryContract
{
    /** @return Collection<int, StatusMaintenance> */
    public function forWorkspace(string $workspaceId): Collection;

    /** @return Collection<int, StatusMaintenance> Pas encore terminées, pour la page publique. */
    public function upcomingForWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): StatusMaintenance;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): StatusMaintenance;

    /** @param  array<string, mixed>  $attributes */
    public function update(StatusMaintenance $maintenance, array $attributes): StatusMaintenance;

    public function delete(StatusMaintenance $maintenance): void;
}
