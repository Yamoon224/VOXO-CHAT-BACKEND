<?php

namespace Tests\Support\Fakes;

use App\Domains\Contacts\Contracts\ContactSegmentRepositoryContract;
use App\Models\ContactSegment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class InMemoryContactSegmentRepository implements ContactSegmentRepositoryContract
{
    /** @var array<string, ContactSegment> */
    private array $segments = [];

    public function __construct(ContactSegment ...$segments)
    {
        foreach ($segments as $segment) {
            $this->segments[$segment->id] = $segment;
        }
    }

    /** @return Collection<int, ContactSegment> */
    public function forWorkspace(string $workspaceId): Collection
    {
        return (new Collection($this->segments))
            ->filter(fn (ContactSegment $segment) => $segment->workspace_id === $workspaceId)
            ->values();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $segmentId): ContactSegment
    {
        $segment = $this->segments[$segmentId] ?? null;

        if ($segment === null || $segment->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $segment;
    }

    public function create(array $attributes): ContactSegment
    {
        $segment = (new ContactSegment)->setRawAttributes($attributes + ['id' => (string) Str::uuid()], true);

        return $this->segments[$segment->id] = $segment;
    }

    public function update(ContactSegment $segment, array $attributes): ContactSegment
    {
        $segment->setRawAttributes($attributes + $segment->getAttributes(), true);

        return $segment;
    }

    public function delete(ContactSegment $segment): void
    {
        unset($this->segments[$segment->id]);
    }
}
