<?php

namespace App\Domains\Contacts\Repositories;

use App\Domains\Contacts\Contracts\ContactSegmentRepositoryContract;
use App\Models\ContactSegment;
use Illuminate\Support\Collection;

final class EloquentContactSegmentRepository implements ContactSegmentRepositoryContract
{
    /** @return Collection<int, ContactSegment> */
    public function forWorkspace(string $workspaceId): Collection
    {
        return ContactSegment::query()->where('workspace_id', $workspaceId)->orderBy('name')->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $segmentId): ContactSegment
    {
        return ContactSegment::query()->where('workspace_id', $workspaceId)->findOrFail($segmentId);
    }

    public function create(array $attributes): ContactSegment
    {
        return ContactSegment::query()->create($attributes);
    }

    public function update(ContactSegment $segment, array $attributes): ContactSegment
    {
        $segment->update($attributes);

        return $segment;
    }

    public function delete(ContactSegment $segment): void
    {
        $segment->delete();
    }
}
