<?php

namespace Tests\Feature\Workspaces;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkspaceManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_compte_voit_tous_ses_espaces_avec_son_role_dans_chacun(): void
    {
        $user = User::factory()->create();
        $this->memberOf(Workspace::factory()->create(['name' => 'Acme']), WorkspaceRole::Owner, $user);
        $this->memberOf(Workspace::factory()->create(['name' => 'Beta']), WorkspaceRole::Viewer, $user);

        $response = $this->actingInWorkspace($user, null)->getJson('/api/v1/workspaces')->assertOk();

        $roles = [];
        foreach ((array) $response->json('data') as $membership) {
            $roles[$membership['name']] = $membership['role'];
        }

        $this->assertSame('workspace_owner', $roles['Acme']);
        $this->assertSame('viewer', $roles['Beta']);
    }

    #[Test]
    public function creer_un_espace_supplementaire_en_fait_le_proprietaire(): void
    {
        $user = User::factory()->create();

        $this->actingInWorkspace($user, null)
            ->postJson('/api/v1/workspaces', ['name' => 'Nouvelle Boutique'])
            ->assertCreated()
            ->assertJsonPath('data.role', 'workspace_owner');
    }

    #[Test]
    public function on_bascule_vers_un_espace_dont_on_est_membre(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $this->memberOf($workspace, WorkspaceRole::Agent, $user);

        $this->actingInWorkspace($user, null)
            ->postJson("/api/v1/workspaces/{$workspace->id}/switch")
            ->assertOk()
            ->assertJsonPath('data.id', $workspace->id);
    }

    #[Test]
    public function on_ne_peut_pas_basculer_vers_un_espace_etranger(): void
    {
        $user = User::factory()->create();
        $foreign = Workspace::factory()->create();

        $this->actingInWorkspace($user, null)
            ->postJson("/api/v1/workspaces/{$foreign->id}/switch")
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'workspace_scope_violation');
    }

    #[Test]
    public function consulter_l_espace_courant_exige_la_permission_workspace_view(): void
    {
        $workspace = Workspace::factory()->create(['name' => 'Acme']);
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($viewer, $workspace)->getJson('/api/v1/workspace')
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme');
    }

    #[Test]
    public function une_requete_sans_espace_actif_est_refusee(): void
    {
        $user = User::factory()->create();

        $this->actingInWorkspace($user, null)->getJson('/api/v1/workspace')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'workspace_required');
    }

    #[Test]
    public function un_agent_ne_peut_pas_modifier_les_reglages_de_l_espace(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->putJson('/api/v1/workspace', ['name' => 'Nouveau nom'])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');
    }

    #[Test]
    public function le_proprietaire_modifie_les_reglages_de_l_espace(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);

        $this->actingInWorkspace($owner, $workspace)
            ->putJson('/api/v1/workspace', ['name' => 'Acme Corp', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme Corp')
            ->assertJsonPath('data.locale', 'en');
    }
}
