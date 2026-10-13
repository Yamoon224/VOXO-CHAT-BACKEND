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
        $segment = (new ContactSegment)->setRawAttributes($this->encodeCriteria($attributes) + ['id' => (string) Str::uuid()], true);

        return $this->segments[$segment->id] = $segment;
    }

    public function update(ContactSegment $segment, array $attributes): ContactSegment
    {
        $segment->setRawAttributes($this->encodeCriteria($attributes) + $segment->getAttributes(), true);

        return $segment;
    }

    /**
     * `criteria` est un cast JSON : comme une vraie ligne, l'attribut brut
     * doit être une chaîne, jamais le tableau PHP que le service fournit.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function encodeCriteria(array $attributes): array
    {
        if (array_key_exists('criteria', $attributes) && is_array($attributes['criteria'])) {
            $attributes['criteria'] = json_encode($attributes['criteria']);
        }

        return $attributes;
    }

    public function delete(ContactSegment $segment): void
    {
        unset($this->segments[$segment->id]);
    }
}
