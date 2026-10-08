<?php

namespace Tests\Support\Fakes;

use App\Domains\Conversations\Contracts\CannedResponseRepositoryContract;
use App\Models\CannedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemoryCannedResponseRepository implements CannedResponseRepositoryContract
{
    /** @var array<string, CannedResponse> */
    private array $responses = [];

    public function forWorkspace(string $workspaceId): Collection
    {
        return (new Collection($this->responses))
            ->filter(fn (CannedResponse $r) => $r->workspace_id === $workspaceId)
            ->sortBy(fn (CannedResponse $r) => $r->title)
            ->values();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): CannedResponse
    {
        $response = $this->responses[$id] ?? null;

        if ($response === null || $response->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $response;
    }

    public function create(array $attributes): CannedResponse
    {
        $response = ModelFactory::cannedResponse($attributes);

        return $this->responses[$response->id] = $response;
    }

    public function update(CannedResponse $response, array $attributes): CannedResponse
    {
        $response->setRawAttributes($attributes + $response->getAttributes(), true);

        return $response;
    }

    public function delete(CannedResponse $response): void
    {
        unset($this->responses[$response->id]);
    }
}
