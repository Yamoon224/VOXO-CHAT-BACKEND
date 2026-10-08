<?php

namespace App\Domains\Workspaces\Contracts;

use App\Models\Workspace;

interface WorkspaceRepositoryContract
{
    public function findOrFail(string $id): Workspace;

    /** @param  array{name: string, slug: string, locale?: string, timezone?: string}  $attributes */
    public function create(array $attributes): Workspace;

    /** @param  array<string, mixed>  $attributes */
    public function update(Workspace $workspace, array $attributes): Workspace;

    public function slugExists(string $slug): bool;
}
