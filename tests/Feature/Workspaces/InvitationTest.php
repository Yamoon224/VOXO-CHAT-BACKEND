<?php

namespace Tests\Feature\Workspaces;

use App\Domains\Notifications\Enums\MailTemplate;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function extractToken(string $actionUrl): string
    {
        return (string) str($actionUrl)->after('token=')->before('&');
    }

    #[Test]
    public function un_administrateur_invite_une_adresse_et_un_e_mail_part(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'nouveau@example.test', 'role' => 'agent'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'nouveau@example.test')
            ->assertJsonPath('data.role', 'agent');

        $this->assertNotNull($this->mailbox()->lastTo('nouveau@example.test', MailTemplate::WorkspaceInvitation));
    }

    #[Test]
    public function on_ne_peut_pas_inviter_quelqu_un_qui_est_deja_membre(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $this->memberOf($workspace, WorkspaceRole::Viewer, User::factory()->create(['email' => 'deja@example.test']));

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'deja@example.test', 'role' => 'agent'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'already_member');

        $this->assertDatabaseMissing('workspace_invitations', ['workspace_id' => $workspace->id, 'email' => 'deja@example.test']);
    }

    #[Test]
    public function une_personne_sans_compte_cree_le_sien_en_acceptant(): void
    {
        $workspace = Workspace::factory()->create(['name' => 'Acme']);
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'nouveau@example.test', 'role' => 'agent']);

        $token = $this->extractToken(
            (string) $this->mailbox()->lastTo('nouveau@example.test', MailTemplate::WorkspaceInvitation)->data['action_url'],
        );

        $this->getJson("/api/v1/invitations/{$token}")
            ->assertOk()
            ->assertJsonPath('data.workspace_name', 'Acme')
            ->assertJsonPath('data.account_exists', false);

        $response = $this->asGuest()->postJson("/api/v1/invitations/{$token}/accept", [
            'name' => 'Nouvelle Recrue',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ])->assertOk();

        $this->assertNotNull($response->json('data.token'));
        $this->assertDatabaseHas('users', ['email' => 'nouveau@example.test']);
    }

    #[Test]
    public function un_compte_existant_doit_se_connecter_pour_accepter(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        User::factory()->create(['email' => 'existant@example.test']);
        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'existant@example.test', 'role' => 'agent']);

        $token = $this->extractToken(
            (string) $this->mailbox()->lastTo('existant@example.test', MailTemplate::WorkspaceInvitation)->data['action_url'],
        );

        $this->asGuest()->postJson("/api/v1/invitations/{$token}/accept", [])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'login_required');
    }

    #[Test]
    public function un_compte_connecte_accepte_l_invitation_de_sa_propre_adresse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $invitee = User::factory()->create(['email' => 'invite@example.test']);
        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'invite@example.test', 'role' => 'viewer']);

        $token = $this->extractToken(
            (string) $this->mailbox()->lastTo('invite@example.test', MailTemplate::WorkspaceInvitation)->data['action_url'],
        );

        $response = $this->actingInWorkspace($invitee, null)
            ->postJson("/api/v1/invitations/{$token}/accept")
            ->assertOk();

        $this->assertNull($response->json('data.token'));
        $this->assertDatabaseHas('workspace_members', ['workspace_id' => $workspace->id, 'user_id' => $invitee->id]);
    }

    #[Test]
    public function une_invitation_revoquee_disparait_de_la_liste(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $invitation = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'x@example.test', 'role' => 'agent'])
            ->json('data.id');

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/invitations/{$invitation}")
            ->assertNoContent();

        $this->assertSame([], $this->actingInWorkspace($admin, $workspace)->getJson('/api/v1/workspace/invitations')->json('data'));
    }

    #[Test]
    public function un_jeton_inconnu_repond_404(): void
    {
        $this->getJson('/api/v1/invitations/jeton-invente')
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'invitation_invalid');
    }
}
