<?php

namespace App\Domains\Automations\Contracts;

use App\Models\AutomationScenario;
use Illuminate\Support\Collection;

interface AutomationScenarioRepositoryContract
{
    /** @return Collection<int, AutomationScenario> Triés par position. */
    public function forWorkspace(string $workspaceId): Collection;

    /** @return Collection<int, AutomationScenario> Seulement les scénarios publiés, triés par position. */
    public function publishedForWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): AutomationScenario;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): AutomationScenario;

    /** @param  array<string, mixed>  $attributes */
    public function update(AutomationScenario $scenario, array $attributes): AutomationScenario;

    public function delete(AutomationScenario $scenario): void;
}
