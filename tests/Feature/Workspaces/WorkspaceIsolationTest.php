<?php

namespace Tests\Feature\Workspaces;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Un espace de travail ne lit ni ne modifie les données d'un autre. Le
 * périmètre vient du jeton, jamais d'un identifiant fourni par la requête.
 */
class WorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function les_membres_d_un_espace_n_apparaissent_pas_dans_un_autre(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $adminA = $this->memberOf($workspaceA, WorkspaceRole::Admin);
        $this->memberOf($workspaceB, WorkspaceRole::Agent);

        $response = $this->actingInWorkspace($adminA, $workspaceA)->getJson('/api/v1/workspace/members')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($adminA->id, $response->json('data.0.user.id'));
    }

    /**
     * Un identifiant de membre d'un autre espace, même deviné correctement,
     * est traité comme inexistant : la recherche est bornée par l'espace
     * avant même de regarder l'identifiant.
     */
    #[Test]
    public function on_ne_peut_pas_modifier_un_membre_d_un_autre_espace(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $adminA = $this->memberOf($workspaceA, WorkspaceRole::Admin);
        $memberB = $this->addMember($workspaceB, WorkspaceRole::Viewer);

        $this->actingInWorkspace($adminA, $workspaceA)
            ->putJson("/api/v1/workspace/members/{$memberB->id}", ['role' => 'agent'])
            ->assertStatus(404);
    }

    #[Test]
    public function les_invitations_d_un_espace_ne_fuient_pas_vers_un_autre(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $adminA = $this->memberOf($workspaceA, WorkspaceRole::Admin);
        $adminB = $this->memberOf($workspaceB, WorkspaceRole::Admin);

        $this->actingInWorkspace($adminB, $workspaceB)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'secret@example.test', 'role' => 'agent']);

        $response = $this->actingInWorkspace($adminA, $workspaceA)->getJson('/api/v1/workspace/invitations')->assertOk();

        $this->assertSame([], $response->json('data'));
    }
}
