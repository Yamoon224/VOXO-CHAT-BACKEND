<?php

namespace App\Domains\Conversations\Contracts;

use App\Models\InboxView;
use Illuminate\Support\Collection;

interface InboxViewRepositoryContract
{
    /** @return Collection<int, InboxView> Triées par position. */
    public function forWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): InboxView;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): InboxView;

    /** @param  array<string, mixed>  $attributes */
    public function update(InboxView $view, array $attributes): InboxView;

    public function delete(InboxView $view): void;
}
