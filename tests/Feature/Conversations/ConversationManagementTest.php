<?php

namespace Tests\Feature\Conversations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_consulte_la_liste_des_conversations(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function un_lecteur_ne_peut_pas_affecter_une_conversation(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);
        $conversation = Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($viewer, $workspace)
            ->putJson("/api/v1/workspace/conversations/{$conversation->id}/assignment", ['user_id' => $viewer->id])
            ->assertStatus(403);
    }

    #[Test]
    public function affecter_a_un_membre_de_l_espace(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/conversations/{$conversation->id}/assignment", ['user_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_user.id', $agent->id);
    }

    #[Test]
    public function affecter_a_quelqu_un_qui_n_est_pas_membre_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $conversation = Conversation::factory()->for($workspace)->create();
        $stranger = User::factory()->create();

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/conversations/{$conversation->id}/assignment", ['user_id' => $stranger->id])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'assignee_not_member');
    }

    #[Test]
    public function changer_le_statut_vers_resolu(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($agent, $workspace)
            ->putJson("/api/v1/workspace/conversations/{$conversation->id}/status", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');
    }

    #[Test]
    public function resumer_une_conversation(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($agent, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/summarize")
            ->assertOk()
            ->assertJsonPath('data.summary', 'Résumé simulé de la conversation.');
    }
}
