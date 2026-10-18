<?php

namespace Tests\Feature\Assistant;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\AgentTool;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentToolManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_declare_un_outil(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/assistant/tools', [
                'name' => 'lookup_order',
                'description' => "Consulte le statut d'une commande.",
                'http_method' => 'GET',
                'url' => 'https://exemple.fr/api/orders/{order_id}',
                'headers' => ['Authorization' => 'Bearer secret-token-123'],
                'parameters' => [
                    ['name' => 'order_id', 'type' => 'string', 'description' => 'Identifiant', 'required' => true, 'in' => 'path'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'lookup_order')
            ->assertJsonPath('data.enabled', true);

        $this->assertSame('••••-123', $response->json('data.headers.Authorization'));
    }

    #[Test]
    public function un_nom_invalide_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/assistant/tools', [
                'name' => 'nom avec espaces',
                'description' => 'Un outil',
                'http_method' => 'GET',
                'url' => 'https://exemple.fr',
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_les_outils(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/assistant/tools')
            ->assertStatus(403);
    }

    #[Test]
    public function modifier_un_outil_conserve_les_en_tetes_non_fournis(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $tool = AgentTool::factory()->create([
            'workspace_id' => $workspace->id,
            'headers' => ['Authorization' => 'Bearer original-secret', 'X-Api-Version' => '1'],
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/assistant/tools/{$tool->id}", [
                'name' => $tool->name,
                'description' => $tool->description,
                'http_method' => 'GET',
                'url' => $tool->url,
                'headers' => ['X-Api-Version' => '2'],
            ])
            ->assertOk();

        $tool->refresh();
        $this->assertSame('Bearer original-secret', $tool->headers['Authorization']);
        $this->assertSame('2', $tool->headers['X-Api-Version']);
        $this->assertSame('••••', $response->json('data.headers.X-Api-Version'));
    }

    #[Test]
    public function supprimer_un_outil(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $tool = AgentTool::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/assistant/tools/{$tool->id}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/assistant/tools')
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function un_espace_etranger_ne_voit_pas_l_outil_d_un_autre(): void
    {
        $workspace = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $foreignTool = AgentTool::factory()->create(['workspace_id' => $other->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/assistant/tools/{$foreignTool->id}")
            ->assertStatus(404);
    }
}
