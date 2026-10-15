<?php

namespace Tests\Support\Fakes;

use App\Domains\Conversations\Contracts\InboxViewRepositoryContract;
use App\Models\InboxView;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemoryInboxViewRepository implements InboxViewRepositoryContract
{
    /** @var array<string, InboxView> */
    private array $views = [];

    public function __construct(InboxView ...$views)
    {
        foreach ($views as $view) {
            $this->views[$view->id] = $view;
        }
    }

    public function forWorkspace(string $workspaceId): Collection
    {
        return (new Collection($this->views))
            ->filter(fn (InboxView $view) => $view->workspace_id === $workspaceId)
            ->sortBy(fn (InboxView $view) => $view->position)
            ->values();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): InboxView
    {
        $view = $this->views[$id] ?? null;

        if ($view === null || $view->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $view;
    }

    public function create(array $attributes): InboxView
    {
        $view = ModelFactory::inboxView($attributes);

        return $this->views[$view->id] = $view;
    }

    public function update(InboxView $view, array $attributes): InboxView
    {
        $view->setRawAttributes($attributes + $view->getAttributes(), true);

        return $view;
    }

    public function delete(InboxView $view): void
    {
        unset($this->views[$view->id]);
    }
}
