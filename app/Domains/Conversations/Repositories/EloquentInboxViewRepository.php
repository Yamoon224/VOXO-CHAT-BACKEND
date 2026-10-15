<?php

namespace App\Domains\Conversations\Repositories;

use App\Domains\Conversations\Contracts\InboxViewRepositoryContract;
use App\Models\InboxView;
use Illuminate\Support\Collection;

final class EloquentInboxViewRepository implements InboxViewRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return InboxView::query()->where('workspace_id', $workspaceId)->orderBy('position')->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): InboxView
    {
        return InboxView::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function create(array $attributes): InboxView
    {
        return InboxView::create($attributes);
    }

    public function update(InboxView $view, array $attributes): InboxView
    {
        $view->update($attributes);

        return $view;
    }

    public function delete(InboxView $view): void
    {
        $view->delete();
    }
}
