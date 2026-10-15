<?php

namespace App\Domains\Conversations\Services;

use App\Domains\Conversations\Contracts\RoutingRuleRepositoryContract;
use App\Domains\Conversations\Enums\RoutingActionType;
use App\Domains\Conversations\Exceptions\AssigneeNotMemberException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Models\RoutingRule;
use Illuminate\Support\Collection;

/** CRUD des règles de routage (section 2.4), évaluées par `RoutingExecutionService`. */
final class RoutingRuleService
{
    public function __construct(
        private readonly RoutingRuleRepositoryContract $rules,
        private readonly MembershipReaderContract $memberships,
    ) {}

    /** @return Collection<int, RoutingRule> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->rules->forWorkspace($scope->workspaceId);
    }

    /**
     * @param  array{name: string, conditions: list<array{field: string, operator: string, value: string}>, action_type: RoutingActionType, assignee_user_ids: list<string>, position: int, is_active: bool}  $attributes
     *
     * @throws AssigneeNotMemberException
     */
    public function create(WorkspaceScope $scope, array $attributes): RoutingRule
    {
        $this->assertAssigneesAreMembers($scope->workspaceId, $attributes['assignee_user_ids']);

        return $this->rules->create([...$attributes, 'workspace_id' => $scope->workspaceId, 'next_round_robin_index' => 0]);
    }

    /**
     * @param  array{name: string, conditions: list<array{field: string, operator: string, value: string}>, action_type: RoutingActionType, assignee_user_ids: list<string>, position: int, is_active: bool}  $attributes
     *
     * @throws AssigneeNotMemberException
     */
    public function update(WorkspaceScope $scope, string $ruleId, array $attributes): RoutingRule
    {
        $this->assertAssigneesAreMembers($scope->workspaceId, $attributes['assignee_user_ids']);

        $rule = $this->rules->findInWorkspaceOrFail($scope->workspaceId, $ruleId);

        // Changer le bassin d'agents retombe sur le début du tourniquet : un
        // curseur qui désignait un index désormais hors bassin n'a plus de sens.
        return $this->rules->update($rule, [...$attributes, 'next_round_robin_index' => 0]);
    }

    public function delete(WorkspaceScope $scope, string $ruleId): void
    {
        $this->rules->delete($this->rules->findInWorkspaceOrFail($scope->workspaceId, $ruleId));
    }

    /**
     * @param  list<string>  $assigneeUserIds
     *
     * @throws AssigneeNotMemberException
     */
    private function assertAssigneesAreMembers(string $workspaceId, array $assigneeUserIds): void
    {
        foreach ($assigneeUserIds as $userId) {
            if ($this->memberships->findForUser($workspaceId, $userId) === null) {
                throw AssigneeNotMemberException::make();
            }
        }
    }
}
