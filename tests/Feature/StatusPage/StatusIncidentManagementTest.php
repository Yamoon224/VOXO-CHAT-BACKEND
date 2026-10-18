<?php

namespace Tests\Feature\StatusPage;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\StatusSubscriber;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusIncidentManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ouvrir_un_incident_cree_sa_premiere_mise_a_jour_et_notifie_les_abonnes(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $subscriber = StatusSubscriber::factory()->create(['workspace_id' => $workspace->id]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/status-page/incidents', [
                'title' => 'Latence élevée',
                'impact' => 'major',
                'status' => 'investigating',
                'body' => 'Nous investiguons une latence anormale.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Latence élevée')
            ->assertJsonPath('data.status', 'investigating');

        $incidentId = $response->json('data.id');

        $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/status-page/incidents/{$incidentId}/updates")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Nous investiguons une latence anormale.');

        $sentRaw = $this->mailbox()->sentRaw();
        $this->assertCount(1, $sentRaw);
        $this->assertSame($subscriber->email, $sentRaw[0]['to']);
        $this->assertStringContainsString('Latence élevée', $sentRaw[0]['subject']);
    }

    #[Test]
    public function une_mise_a_jour_resolue_horodate_la_resolution(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $created = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/status-page/incidents', [
                'title' => 'Panne',
                'impact' => 'critical',
                'status' => 'investigating',
                'body' => 'Panne détectée.',
            ])
            ->assertCreated();

        $resolved = $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/status-page/incidents/{$created->json('data.id')}/updates", [
                'status' => 'resolved',
                'body' => 'Tout est revenu à la normale.',
            ])
            ->assertOk();

        $this->assertNotNull($resolved->json('data.resolved_at'));
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_les_incidents(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/status-page/incidents', [
                'title' => 'Panne',
                'impact' => 'minor',
                'status' => 'investigating',
                'body' => 'Panne détectée.',
            ])
            ->assertStatus(403);
    }
}
