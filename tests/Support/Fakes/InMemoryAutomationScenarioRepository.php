<?php

namespace Tests\Support\Fakes;

use App\Domains\Automations\Contracts\AutomationScenarioRepositoryContract;
use App\Domains\Automations\Enums\AutomationStatus;
use App\Models\AutomationScenario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemoryAutomationScenarioRepository implements AutomationScenarioRepositoryContract
{
    /** @var array<string, AutomationScenario> */
    private array $scenarios = [];

    public function __construct(AutomationScenario ...$scenarios)
    {
        foreach ($scenarios as $scenario) {
            $this->scenarios[$scenario->id] = $scenario;
        }
    }

    public function forWorkspace(string $workspaceId): Collection
    {
        return $this->scoped($workspaceId);
    }

    public function publishedForWorkspace(string $workspaceId): Collection
    {
        return $this->scoped($workspaceId)->filter(fn (AutomationScenario $s) => $s->status === AutomationStatus::Published)->values();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): AutomationScenario
    {
        $scenario = $this->scenarios[$id] ?? null;

        if ($scenario === null || $scenario->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $scenario;
    }

    public function create(array $attributes): AutomationScenario
    {
        $scenario = ModelFactory::automationScenario($attributes);

        return $this->scenarios[$scenario->id] = $scenario;
    }

    public function update(AutomationScenario $scenario, array $attributes): AutomationScenario
    {
        foreach (['conditions', 'steps'] as $jsonField) {
            if (array_key_exists($jsonField, $attributes) && is_array($attributes[$jsonField])) {
                $attributes[$jsonField] = json_encode($attributes[$jsonField]);
            }
        }

        $scenario->setRawAttributes($attributes + $scenario->getAttributes(), true);

        return $scenario;
    }

    public function delete(AutomationScenario $scenario): void
    {
        unset($this->scenarios[$scenario->id]);
    }

    /** @return Collection<int, AutomationScenario> */
    private function scoped(string $workspaceId): Collection
    {
        return (new Collection($this->scenarios))
            ->filter(fn (AutomationScenario $s) => $s->workspace_id === $workspaceId)
            ->sortBy(fn (AutomationScenario $s) => $s->position)
            ->values();
    }
}
