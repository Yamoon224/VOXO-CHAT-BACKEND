<?php

namespace App\Domains\Conversations\Services;

use App\Domains\Conversations\Contracts\InboxViewRepositoryContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\InboxView;
use Illuminate\Support\Collection;

/** Sous-boîtes (section 2.4) : des filtres de la boîte de réception, nommés et enregistrés. */
final class InboxViewService
{
    public function __construct(private readonly InboxViewRepositoryContract $views) {}

    /** @return Collection<int, InboxView> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->views->forWorkspace($scope->workspaceId);
    }

    /** @param  array<string, mixed>  $filters */
    public function create(WorkspaceScope $scope, string $name, array $filters, int $position): InboxView
    {
        return $this->views->create([
            'workspace_id' => $scope->workspaceId,
            'name' => $name,
            'filters' => $filters,
            'position' => $position,
        ]);
    }

    /** @param  array<string, mixed>  $filters */
    public function update(WorkspaceScope $scope, string $viewId, string $name, array $filters, int $position): InboxView
    {
        $view = $this->views->findInWorkspaceOrFail($scope->workspaceId, $viewId);

        return $this->views->update($view, ['name' => $name, 'filters' => $filters, 'position' => $position]);
    }

    public function delete(WorkspaceScope $scope, string $viewId): void
    {
        $this->views->delete($this->views->findInWorkspaceOrFail($scope->workspaceId, $viewId));
    }
}
