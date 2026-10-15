<?php

namespace App\Domains\Automations\Services;

use App\Domains\Automations\Contracts\AutomationScenarioRepositoryContract;
use App\Domains\Automations\Enums\AutomationActionType;
use App\Domains\Automations\Exceptions\AutomationAssigneeNotMemberException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Models\AutomationScenario;
use Illuminate\Support\Collection;

/** CRUD des scénarios d'automatisation (section 2.8), évalués par `AutomationExecutionService`. */
final class AutomationScenarioService
{
    public function __construct(
        private readonly AutomationScenarioRepositoryContract $scenarios,
        private readonly MembershipReaderContract $memberships,
    ) {}

    /** @return Collection<int, AutomationScenario> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->scenarios->forWorkspace($scope->workspaceId);
    }

    public function find(WorkspaceScope $scope, string $scenarioId): AutomationScenario
    {
        return $this->scenarios->findInWorkspaceOrFail($scope->workspaceId, $scenarioId);
    }

    /**
     * @param  array{name: string, position: int, status: string, trigger_type: string, conditions: list<array{field: string, operator: string, value: string}>, steps: list<array{type: string, config: array<string, mixed>}>}  $attributes
     *
     * @throws AutomationAssigneeNotMemberException
     */
    public function create(WorkspaceScope $scope, array $attributes): AutomationScenario
    {
        $this->assertAssigneesAreMembers($scope->workspaceId, $attributes['steps']);

        return $this->scenarios->create([...$attributes, 'workspace_id' => $scope->workspaceId]);
    }

    /**
     * @param  array{name: string, position: int, status: string, trigger_type: string, conditions: list<array{field: string, operator: string, value: string}>, steps: list<array{type: string, config: array<string, mixed>}>}  $attributes
     *
     * @throws AutomationAssigneeNotMemberException
     */
    public function update(WorkspaceScope $scope, string $scenarioId, array $attributes): AutomationScenario
    {
        $this->assertAssigneesAreMembers($scope->workspaceId, $attributes['steps']);

        return $this->scenarios->update($this->find($scope, $scenarioId), $attributes);
    }

    public function delete(WorkspaceScope $scope, string $scenarioId): void
    {
        $this->scenarios->delete($this->find($scope, $scenarioId));
    }

    /**
     * @param  list<array{type: string, config: array<string, mixed>}>  $steps
     *
     * @throws AutomationAssigneeNotMemberException
     */
    private function assertAssigneesAreMembers(string $workspaceId, array $steps): void
    {
        foreach ($steps as $step) {
            if ($step['type'] !== AutomationActionType::AssignUser->value) {
                continue;
            }

            $userId = $step['config']['user_id'] ?? null;

            if (! is_string($userId) || $this->memberships->findForUser($workspaceId, $userId) === null) {
                throw AutomationAssigneeNotMemberException::make();
            }
        }
    }
}
