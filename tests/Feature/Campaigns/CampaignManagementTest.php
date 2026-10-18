<?php

namespace Tests\Feature\Campaigns;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\ContactSegment;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_cree_une_campagne_en_brouillon(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id]);
        $template = CampaignTemplate::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/campaigns', [
                'name' => 'Promotion automne',
                'channel' => 'email',
                'segment_id' => $segment->id,
                'template_id' => $template->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Promotion automne')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.stats.pending', 0);
    }

    #[Test]
    public function creer_une_campagne_avec_un_segment_d_un_autre_espace_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $foreignSegment = ContactSegment::factory()->create(['workspace_id' => $other->id]);
        $template = CampaignTemplate::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/campaigns', [
                'name' => 'Promotion automne',
                'channel' => 'email',
                'segment_id' => $foreignSegment->id,
                'template_id' => $template->id,
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_les_campagnes(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/campaigns')
            ->assertStatus(403);
    }

    #[Test]
    public function une_campagne_deja_envoyee_ne_peut_plus_etre_modifiee(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id]);
        $template = CampaignTemplate::factory()->create(['workspace_id' => $workspace->id]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'segment_id' => $segment->id,
            'template_id' => $template->id,
            'status' => 'sent',
        ]);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/campaigns/{$campaign->id}", [
                'name' => 'Nouveau nom',
                'channel' => 'email',
                'segment_id' => $segment->id,
                'template_id' => $template->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'campaign_already_sent');
    }

    #[Test]
    public function supprimer_une_campagne(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $campaign = Campaign::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/campaigns/{$campaign->id}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/campaigns')
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function un_espace_etranger_ne_voit_pas_la_campagne_d_un_autre(): void
    {
        $workspace = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $foreignCampaign = Campaign::factory()->create(['workspace_id' => $other->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/campaigns/{$foreignCampaign->id}")
            ->assertStatus(404);
    }
}
