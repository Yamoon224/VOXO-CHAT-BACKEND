<?php

namespace App\Domains\Conversations\Repositories;

use App\Domains\Conversations\Contracts\CannedResponseRepositoryContract;
use App\Models\CannedResponse;
use Illuminate\Support\Collection;

final class EloquentCannedResponseRepository implements CannedResponseRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return CannedResponse::query()->where('workspace_id', $workspaceId)->oldest('title')->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): CannedResponse
    {
        return CannedResponse::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function create(array $attributes): CannedResponse
    {
        return CannedResponse::create($attributes);
    }

    public function update(CannedResponse $response, array $attributes): CannedResponse
    {
        $response->update($attributes);

        return $response;
    }

    public function delete(CannedResponse $response): void
    {
        $response->delete();
    }
}
