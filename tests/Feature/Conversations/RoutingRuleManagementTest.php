<?php

namespace Tests\Feature\Conversations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoutingRuleManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_cree_une_regle_de_routage(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/routing-rules', [
                'name' => 'Facturation',
                'conditions' => [['field' => 'message', 'operator' => 'contains', 'value' => 'facture']],
                'action_type' => 'assign_user',
                'assignee_user_ids' => [$agent->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Facturation')
            ->assertJsonPath('data.action_type', 'assign_user');
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_le_routage(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/routing-rules')
            ->assertStatus(403);
    }

    #[Test]
    public function affecter_a_un_agent_etranger_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/routing-rules', [
                'name' => 'Facturation',
                'action_type' => 'assign_user',
                'assignee_user_ids' => ['00000000-0000-0000-0000-000000000000'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'assignee_not_member');
    }

    #[Test]
    public function supprimer_une_regle(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $created = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/routing-rules', [
                'name' => 'Règle',
                'action_type' => 'assign_user',
                'assignee_user_ids' => [$admin->id],
            ])
            ->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/routing-rules/{$created->json('data.id')}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/routing-rules')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
