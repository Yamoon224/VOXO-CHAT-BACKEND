<?php

namespace Tests\Feature\Integrations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiKeyManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_emet_puis_liste_une_cle_d_api(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $issued = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/integrations/api-keys', ['name' => 'Zapier'])
            ->assertCreated();

        $this->assertNotEmpty($issued->json('data.token'));

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/integrations/api-keys')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Zapier')
            ->assertJsonMissingPath('data.0.token');
    }

    #[Test]
    public function un_agent_ne_peut_pas_emettre_de_cle_d_api(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/integrations/api-keys', ['name' => 'Zapier'])
            ->assertStatus(403);
    }

    #[Test]
    public function revoquer_une_cle_la_retire_de_la_liste(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $issued = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/integrations/api-keys', ['name' => 'Zapier'])
            ->assertCreated();

        $key = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/integrations/api-keys')
            ->json('data.0.id');

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/integrations/api-keys/{$key}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/integrations/api-keys')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function une_cle_d_api_authentifie_les_requetes_de_l_espace(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $issued = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/integrations/api-keys', ['name' => 'Zapier'])
            ->assertCreated();

        $token = $issued->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/workspace/integrations/api-keys')
            ->assertOk();
    }
}
