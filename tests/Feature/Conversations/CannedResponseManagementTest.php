<?php

namespace Tests\Feature\Conversations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CannedResponseManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_cree_une_reponse_pre_enregistree(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/canned-responses', ['title' => 'Bienvenue', 'body' => 'Bonjour et bienvenue !'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Bienvenue');
    }

    #[Test]
    public function deux_reponses_du_meme_titre_sont_refusees(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/canned-responses', ['title' => 'Bienvenue', 'body' => 'Bonjour !']);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/canned-responses', ['title' => 'Bienvenue', 'body' => 'Un autre message.'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'canned_response_title_taken');
    }

    #[Test]
    public function un_lecteur_n_a_pas_acces_aux_reponses_pre_enregistrees(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($viewer, $workspace)
            ->getJson('/api/v1/workspace/canned-responses')
            ->assertStatus(403);
    }

    #[Test]
    public function supprimer_une_reponse_pre_enregistree(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $created = $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/canned-responses', ['title' => 'Au revoir', 'body' => 'Merci, à bientôt !'])
            ->assertCreated();

        $this->actingInWorkspace($agent, $workspace)
            ->deleteJson("/api/v1/workspace/canned-responses/{$created->json('data.id')}")
            ->assertStatus(204);
    }
}
