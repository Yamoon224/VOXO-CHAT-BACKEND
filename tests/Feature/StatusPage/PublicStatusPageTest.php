<?php

namespace Tests\Feature\StatusPage;

use App\Models\StatusIncident;
use App\Models\StatusMaintenance;
use App\Models\StatusProbe;
use App\Models\StatusProbeCheck;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicStatusPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_vue_publique_assemble_sondes_incidents_et_maintenances(): void
    {
        $workspace = Workspace::factory()->create();
        $probe = StatusProbe::factory()->create(['workspace_id' => $workspace->id, 'name' => 'API', 'status' => 'up']);
        StatusProbeCheck::factory()->create(['workspace_id' => $workspace->id, 'probe_id' => $probe->id, 'status' => 'up']);
        $ongoingIncident = StatusIncident::factory()->create(['workspace_id' => $workspace->id, 'title' => 'Latence']);
        StatusIncident::factory()->create(['workspace_id' => $workspace->id, 'resolved_at' => now()]);
        StatusMaintenance::factory()->create(['workspace_id' => $workspace->id, 'status' => 'scheduled']);
        StatusMaintenance::factory()->create(['workspace_id' => $workspace->id, 'status' => 'completed']);

        $response = $this->getJson("/api/v1/public/status/{$workspace->id}")->assertOk();

        $this->assertCount(1, $response->json('data.probes'));
        $this->assertSame('API', $response->json('data.probes.0.name'));
        $this->assertEquals(1.0, $response->json('data.probes.0.uptime_ratio'));

        $this->assertCount(1, $response->json('data.ongoing_incidents'));
        $this->assertSame($ongoingIncident->id, $response->json('data.ongoing_incidents.0.id'));

        $this->assertCount(1, $response->json('data.upcoming_maintenances'));
    }

    #[Test]
    public function un_espace_sans_activite_renvoie_des_listes_vides(): void
    {
        $workspace = Workspace::factory()->create();

        $this->getJson("/api/v1/public/status/{$workspace->id}")
            ->assertOk()
            ->assertJsonPath('data.probes', [])
            ->assertJsonPath('data.ongoing_incidents', [])
            ->assertJsonPath('data.upcoming_maintenances', []);
    }
}
