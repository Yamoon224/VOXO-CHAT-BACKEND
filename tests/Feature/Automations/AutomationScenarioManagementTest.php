<?php

namespace Tests\Feature\Automations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutomationScenarioManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_cree_un_scenario_en_brouillon_par_defaut(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/automations', [
                'name' => 'Accueil',
                'trigger_type' => 'conversation_opened',
                'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bonjour !']]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Accueil')
            ->assertJsonPath('data.status', 'draft');
    }

    #[Test]
    public function publier_un_scenario(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $created = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/automations', [
                'name' => 'Accueil',
                'trigger_type' => 'conversation_opened',
                'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bonjour !']]],
            ])->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/automations/{$created->json('data.id')}", [
                'name' => 'Accueil',
                'status' => 'published',
                'trigger_type' => 'conversation_opened',
                'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bonjour !']]],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_les_automatisations(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/automations')
            ->assertStatus(403);
    }

    #[Test]
    public function affecter_a_un_agent_etranger_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/automations', [
                'name' => 'Escalade',
                'trigger_type' => 'conversation_opened',
                'steps' => [['type' => 'assign_user', 'config' => ['user_id' => '00000000-0000-0000-0000-000000000000']]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'automation_assignee_not_member');
    }

    #[Test]
    public function supprimer_un_scenario(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $created = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/automations', [
                'name' => 'Scénario',
                'trigger_type' => 'conversation_opened',
            ])->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/automations/{$created->json('data.id')}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/automations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
