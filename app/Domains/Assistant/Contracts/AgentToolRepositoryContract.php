<?php

namespace App\Domains\Assistant\Contracts;

use App\Models\AgentTool;
use Illuminate\Support\Collection;

interface AgentToolRepositoryContract
{
    /** @return Collection<int, AgentTool> */
    public function forWorkspace(string $workspaceId): Collection;

    /** @return Collection<int, AgentTool> Actifs uniquement, pour l'agent IA. */
    public function enabledForWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): AgentTool;

    public function findByName(string $workspaceId, string $name): ?AgentTool;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): AgentTool;

    /** @param  array<string, mixed>  $attributes */
    public function update(AgentTool $tool, array $attributes): AgentTool;

    public function delete(AgentTool $tool): void;
}
