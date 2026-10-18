<?php

namespace App\Domains\StatusPage\Repositories;

use App\Domains\StatusPage\Contracts\StatusSubscriberRepositoryContract;
use App\Models\StatusSubscriber;
use Illuminate\Support\Collection;

final class EloquentStatusSubscriberRepository implements StatusSubscriberRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return StatusSubscriber::query()->where('workspace_id', $workspaceId)->latest('created_at')->get();
    }

    public function findInWorkspace(string $workspaceId, string $id): ?StatusSubscriber
    {
        return StatusSubscriber::query()->where('workspace_id', $workspaceId)->find($id);
    }

    public function findById(string $id): ?StatusSubscriber
    {
        return StatusSubscriber::query()->find($id);
    }

    public function findByEmail(string $workspaceId, string $email): ?StatusSubscriber
    {
        return StatusSubscriber::query()->where('workspace_id', $workspaceId)->where('email', $email)->first();
    }

    public function create(array $attributes): StatusSubscriber
    {
        return StatusSubscriber::query()->create($attributes);
    }

    public function delete(StatusSubscriber $subscriber): void
    {
        $subscriber->delete();
    }
}
