<?php

namespace App\Domains\Conversations\Contracts;

use App\Models\RoutingRule;
use Illuminate\Support\Collection;

interface RoutingRuleRepositoryContract
{
    /** @return Collection<int, RoutingRule> Triées par position, pour l'ordre d'évaluation. */
    public function forWorkspace(string $workspaceId): Collection;

    /** @return Collection<int, RoutingRule> Seulement les règles actives, triées par position. */
    public function activeForWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): RoutingRule;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): RoutingRule;

    /** @param  array<string, mixed>  $attributes */
    public function update(RoutingRule $rule, array $attributes): RoutingRule;

    public function delete(RoutingRule $rule): void;
}
