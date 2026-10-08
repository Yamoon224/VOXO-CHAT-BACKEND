<?php

namespace Tests\Support\Fakes;

use App\Domains\Workspaces\Contracts\WorkspaceRepositoryContract;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class InMemoryWorkspaceRepository implements WorkspaceRepositoryContract
{
    /** @var array<string, Workspace> */
    private array $workspaces = [];

    public function __construct(Workspace ...$workspaces)
    {
        foreach ($workspaces as $workspace) {
            $this->workspaces[$workspace->id] = $workspace;
        }
    }

    public function findOrFail(string $id): Workspace
    {
        return $this->workspaces[$id] ?? throw new ModelNotFoundException;
    }

    public function create(array $attributes): Workspace
    {
        $workspace = ModelFactory::workspace($attributes['name'], $attributes['slug']);

        return $this->workspaces[$workspace->id] = $workspace;
    }

    public function update(Workspace $workspace, array $attributes): Workspace
    {
        $workspace->setRawAttributes($attributes + $workspace->getAttributes(), true);

        return $workspace;
    }

    public function slugExists(string $slug): bool
    {
        foreach ($this->workspaces as $workspace) {
            if ($workspace->slug === $slug) {
                return true;
            }
        }

        return false;
    }
}
