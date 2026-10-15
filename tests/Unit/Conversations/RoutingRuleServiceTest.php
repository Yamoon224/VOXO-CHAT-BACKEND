<?php

namespace Tests\Unit\Conversations;

use App\Domains\Conversations\Enums\RoutingActionType;
use App\Domains\Conversations\Exceptions\AssigneeNotMemberException;
use App\Domains\Conversations\Services\RoutingRuleService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemoryRoutingRuleRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class RoutingRuleServiceTest extends TestCase
{
    #[Test]
    public function creer_une_regle_avec_un_agent_etranger_est_refuse(): void
    {
        $service = new RoutingRuleService(new InMemoryRoutingRuleRepository, new InMemoryMembershipRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $this->expectException(AssigneeNotMemberException::class);

        $service->create($scope, [
            'name' => 'Règle',
            'conditions' => [],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['etranger'],
            'position' => 0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function creer_une_regle_avec_des_membres_reussit(): void
    {
        $memberships = new InMemoryMembershipRepository;
        $memberships->add('workspace-1', 'user-1', WorkspaceRole::Agent);
        $service = new RoutingRuleService(new InMemoryRoutingRuleRepository, $memberships);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $rule = $service->create($scope, [
            'name' => 'Règle',
            'conditions' => [],
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => ['user-1'],
            'position' => 0,
            'is_active' => true,
        ]);

        $this->assertSame('workspace-1', $rule->workspace_id);
        $this->assertSame(0, $rule->next_round_robin_index);
    }

    #[Test]
    public function modifier_le_bassin_remet_le_tourniquet_a_zero(): void
    {
        $memberships = new InMemoryMembershipRepository;
        $memberships->add('workspace-1', 'user-1', WorkspaceRole::Agent);
        $memberships->add('workspace-1', 'user-2', WorkspaceRole::Agent);
        $rule = ModelFactory::routingRule(['workspace_id' => 'workspace-1', 'next_round_robin_index' => 3]);
        $service = new RoutingRuleService(new InMemoryRoutingRuleRepository($rule), $memberships);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $updated = $service->update($scope, $rule->id, [
            'name' => 'Règle modifiée',
            'conditions' => [],
            'action_type' => RoutingActionType::RoundRobin,
            'assignee_user_ids' => ['user-1', 'user-2'],
            'position' => 0,
            'is_active' => true,
        ]);

        $this->assertSame(0, $updated->next_round_robin_index);
    }
}
