<?php

namespace Tests\Feature\StatusPage;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\StatusProbe;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusProbeManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_cree_une_sonde(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/status-page/probes', ['name' => 'API', 'url' => 'https://exemple.fr/health'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'API')
            ->assertJsonPath('data.status', 'unknown')
            ->assertJsonPath('data.check_interval_minutes', 5);
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_les_sondes(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/status-page/probes')
            ->assertStatus(403);
    }

    #[Test]
    public function modifier_et_supprimer_une_sonde(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $probe = StatusProbe::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/status-page/probes/{$probe->id}", ['name' => 'API renommée', 'url' => 'https://exemple.fr/ping'])
            ->assertOk()
            ->assertJsonPath('data.name', 'API renommée');

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/status-page/probes/{$probe->id}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/status-page/probes')
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function un_espace_etranger_ne_voit_pas_la_sonde_d_un_autre(): void
    {
        $workspace = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $foreignProbe = StatusProbe::factory()->create(['workspace_id' => $other->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/status-page/probes/{$foreignProbe->id}", ['name' => 'X', 'url' => 'https://exemple.fr'])
            ->assertStatus(404);
    }
}
