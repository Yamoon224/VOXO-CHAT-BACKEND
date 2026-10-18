<?php

namespace App\Domains\StatusPage\Repositories;

use App\Domains\StatusPage\Contracts\StatusIncidentRepositoryContract;
use App\Models\StatusIncident;
use Illuminate\Support\Collection;

final class EloquentStatusIncidentRepository implements StatusIncidentRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return StatusIncident::query()->where('workspace_id', $workspaceId)->latest('started_at')->get();
    }

    public function ongoingForWorkspace(string $workspaceId): Collection
    {
        return StatusIncident::query()
            ->where('workspace_id', $workspaceId)
            ->whereNull('resolved_at')
            ->latest('started_at')
            ->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): StatusIncident
    {
        return StatusIncident::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function create(array $attributes): StatusIncident
    {
        return StatusIncident::query()->create($attributes);
    }

    public function update(StatusIncident $incident, array $attributes): StatusIncident
    {
        $incident->update($attributes);

        return $incident;
    }

    public function delete(StatusIncident $incident): void
    {
        $incident->delete();
    }
}
