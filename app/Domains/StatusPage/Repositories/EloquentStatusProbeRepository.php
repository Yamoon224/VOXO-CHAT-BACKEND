<?php

namespace App\Domains\StatusPage\Repositories;

use App\Domains\StatusPage\Contracts\StatusProbeRepositoryContract;
use App\Models\StatusProbe;
use Illuminate\Support\Collection;

final class EloquentStatusProbeRepository implements StatusProbeRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return StatusProbe::query()->where('workspace_id', $workspaceId)->latest('created_at')->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): StatusProbe
    {
        return StatusProbe::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function findById(string $id): ?StatusProbe
    {
        return StatusProbe::query()->find($id);
    }

    public function dueForCheck(): Collection
    {
        return StatusProbe::query()
            ->get()
            ->filter(fn (StatusProbe $probe) => $probe->isDueForCheck(now()))
            ->values();
    }

    public function create(array $attributes): StatusProbe
    {
        return StatusProbe::query()->create($attributes);
    }

    public function update(StatusProbe $probe, array $attributes): StatusProbe
    {
        $probe->update($attributes);

        return $probe;
    }

    public function delete(StatusProbe $probe): void
    {
        $probe->delete();
    }
}
