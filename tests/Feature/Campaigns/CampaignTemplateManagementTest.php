<?php

namespace Tests\Feature\Campaigns;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\CampaignTemplate;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_cree_un_modele_e_mail(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/campaign-templates', [
                'name' => 'Relance',
                'channel' => 'email',
                'subject' => 'Bonjour {{name}}',
                'body' => 'Contenu pour {{email}}',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Relance')
            ->assertJsonPath('data.subject', 'Bonjour {{name}}');
    }

    #[Test]
    public function un_modele_e_mail_sans_objet_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/campaign-templates', ['name' => 'Relance', 'channel' => 'email', 'body' => 'Contenu'])
            ->assertStatus(422);
    }

    #[Test]
    public function un_modele_chat_n_exige_pas_d_objet(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/campaign-templates', ['name' => 'Relance chat', 'channel' => 'chat', 'body' => 'Bonjour {{name}}'])
            ->assertCreated()
            ->assertJsonPath('data.subject', null);
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_les_modeles(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/campaign-templates')
            ->assertStatus(403);
    }

    #[Test]
    public function supprimer_un_modele(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $template = CampaignTemplate::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/campaign-templates/{$template->id}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/campaign-templates')
            ->assertJsonCount(0, 'data');
    }
}
