<?php

namespace Tests\Feature\Conversations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InboxViewManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_enregistre_la_vue_courante_comme_sous_boite(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/inbox-views', [
                'name' => 'Escaladées',
                'filters' => ['needs_human' => true],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Escaladées')
            ->assertJsonPath('data.filters.needs_human', true);
    }

    #[Test]
    public function un_viewer_ne_peut_pas_creer_de_sous_boite_mais_peut_les_consulter(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($viewer, $workspace)
            ->postJson('/api/v1/workspace/inbox-views', ['name' => 'Vue'])
            ->assertStatus(403);

        $this->actingInWorkspace($viewer, $workspace)
            ->getJson('/api/v1/workspace/inbox-views')
            ->assertOk();
    }

    #[Test]
    public function supprimer_une_sous_boite(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $created = $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/inbox-views', ['name' => 'Vue', 'filters' => ['channel' => 'email']])
            ->assertCreated();

        $this->actingInWorkspace($agent, $workspace)
            ->deleteJson("/api/v1/workspace/inbox-views/{$created->json('data.id')}")
            ->assertStatus(204);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/inbox-views')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
