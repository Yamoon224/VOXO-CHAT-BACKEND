<?php

namespace Tests\Feature\Workspaces;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_change_le_role_d_un_membre(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->addMember($workspace, WorkspaceRole::Admin);
        $target = $this->addMember($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($admin->user, $workspace)
            ->putJson("/api/v1/workspace/members/{$target->id}", ['role' => 'agent'])
            ->assertOk()
            ->assertJsonPath('data.role', 'agent');
    }

    #[Test]
    public function un_viewer_ne_peut_pas_gerer_l_equipe(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->addMember($workspace, WorkspaceRole::Viewer);
        $target = $this->addMember($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($viewer->user, $workspace)
            ->putJson("/api/v1/workspace/members/{$target->id}", ['role' => 'viewer'])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');
    }

    #[Test]
    public function le_proprietaire_ne_peut_pas_etre_retire(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->addMember($workspace, WorkspaceRole::Admin);
        $owner = $this->addMember($workspace, WorkspaceRole::Owner);

        $this->actingInWorkspace($admin->user, $workspace)
            ->deleteJson("/api/v1/workspace/members/{$owner->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'owner_protected');
    }

    #[Test]
    public function on_ne_peut_pas_modifier_sa_propre_adhesion(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->addMember($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin->user, $workspace)
            ->deleteJson("/api/v1/workspace/members/{$admin->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'self_membership_change');
    }

    #[Test]
    public function retirer_un_membre_le_fait_disparaitre_de_la_liste(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->addMember($workspace, WorkspaceRole::Admin);
        $target = $this->addMember($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($admin->user, $workspace)
            ->deleteJson("/api/v1/workspace/members/{$target->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workspace_members', ['id' => $target->id]);
    }
}
