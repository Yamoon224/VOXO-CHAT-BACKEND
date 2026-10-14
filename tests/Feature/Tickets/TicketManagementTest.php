<?php

namespace Tests\Feature\Tickets;

use App\Domains\Notifications\Enums\MailTemplate;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_transforme_une_conversation_en_ticket_et_le_client_recoit_son_lien(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $conversation = Conversation::factory()->create([
            'workspace_id' => $workspace->id,
            'visitor_email' => 'client@exemple.fr',
            'visitor_name' => 'Client Exemple',
            'subject' => 'Problème de livraison',
        ]);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/ticket", ['priority' => 'high'])
            ->assertCreated()
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.conversation.subject', 'Problème de livraison')
            ->assertJsonPath('data.contact.email', 'client@exemple.fr');

        $this->assertNotNull($this->mailbox()->lastTo('client@exemple.fr', MailTemplate::TicketPortalLink));
        $this->assertDatabaseHas('contacts', ['workspace_id' => $workspace->id, 'email' => 'client@exemple.fr']);
    }

    #[Test]
    public function une_conversation_sans_adresse_connue_ouvre_un_ticket_sans_lien_envoye(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/ticket", [])
            ->assertCreated()
            ->assertJsonPath('data.priority', 'normal')
            ->assertJsonPath('data.contact', null);

        $this->assertSame([], $this->mailbox()->sent());
    }

    #[Test]
    public function une_conversation_ne_peut_avoir_deux_tickets(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/ticket", [])
            ->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/ticket", [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ticket_already_exists');
    }

    #[Test]
    public function un_viewer_ne_peut_pas_ouvrir_de_ticket_mais_peut_le_consulter(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($viewer, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/ticket", [])
            ->assertStatus(403);

        $this->actingInWorkspace($viewer, $workspace)
            ->getJson('/api/v1/workspace/tickets')
            ->assertOk();
    }

    #[Test]
    public function un_agent_modifie_la_priorite_et_l_echeance_d_un_ticket(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $opened = $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/ticket", [])
            ->assertCreated();

        $ticketId = $opened->json('data.id');

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/tickets/{$ticketId}", ['priority' => 'urgent', 'due_at' => '2026-12-01T00:00:00Z'])
            ->assertOk()
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.due_at', '2026-12-01T00:00:00+00:00');
    }

    #[Test]
    public function la_liste_se_filtre_par_priorite(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $low = Conversation::factory()->create(['workspace_id' => $workspace->id]);
        $urgent = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)->postJson("/api/v1/workspace/conversations/{$low->id}/ticket", ['priority' => 'low']);
        $this->actingInWorkspace($admin, $workspace)->postJson("/api/v1/workspace/conversations/{$urgent->id}/ticket", ['priority' => 'urgent']);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/tickets?priority=urgent')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('urgent', $response->json('data.0.priority'));
    }
}
