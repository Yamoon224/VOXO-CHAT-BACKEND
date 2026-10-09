<?php

namespace App\Domains\Workspaces\Repositories;

use App\Domains\Workspaces\Contracts\WorkspacePlatformReaderContract;
use App\Domains\Workspaces\Contracts\WorkspaceRepositoryContract;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentWorkspaceRepository implements WorkspacePlatformReaderContract, WorkspaceRepositoryContract
{
    public function findOrFail(string $id): Workspace
    {
        return Workspace::query()->findOrFail($id);
    }

    public function create(array $attributes): Workspace
    {
        return Workspace::create($attributes);
    }

    public function update(Workspace $workspace, array $attributes): Workspace
    {
        $workspace->update($attributes);

        return $workspace;
    }

    public function slugExists(string $slug): bool
    {
        return Workspace::query()->where('slug', $slug)->exists();
    }

    public function listAllPaginated(int $perPage): LengthAwarePaginator
    {
        return Workspace::query()
            ->withCount('members')
            ->with('subscription.plan')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
