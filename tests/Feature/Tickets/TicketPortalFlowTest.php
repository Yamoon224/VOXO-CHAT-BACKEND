<?php

namespace Tests\Feature\Tickets;

use App\Domains\Notifications\Enums\MailTemplate;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Ticket;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketPortalFlowTest extends TestCase
{
    use RefreshDatabase;

    private function extractToken(string $actionUrl): string
    {
        return (string) str($actionUrl)->after('token=')->before('&');
    }

    #[Test]
    public function un_client_ouvre_une_demande_et_recoit_un_lien_de_suivi(): void
    {
        $workspace = Workspace::factory()->create();

        $this->postJson("/api/v1/public/tickets/{$workspace->id}/submissions", [
            'email' => 'client@exemple.fr',
            'name' => 'Client Exemple',
            'subject' => 'Problème de livraison',
            'message' => "Ma commande n'est jamais arrivée.",
        ])
            ->assertCreated()
            ->assertJsonPath('data.subject', 'Problème de livraison')
            ->assertJsonPath('data.status', 'open');

        $this->assertNotNull($this->mailbox()->lastTo('client@exemple.fr', MailTemplate::TicketPortalLink));
        $this->assertDatabaseHas('conversations', ['workspace_id' => $workspace->id, 'channel' => 'ticket']);
    }

    #[Test]
    public function le_lien_recu_donne_acces_a_la_liste_et_au_detail_du_ticket(): void
    {
        $workspace = Workspace::factory()->create();

        $this->postJson("/api/v1/public/tickets/{$workspace->id}/submissions", [
            'email' => 'client@exemple.fr',
            'subject' => 'Question facturation',
            'message' => 'Je ne comprends pas ma dernière facture.',
        ])->assertCreated();

        $token = $this->extractToken(
            (string) $this->mailbox()->lastTo('client@exemple.fr', MailTemplate::TicketPortalLink)->data['action_url'],
        );

        $list = $this->getJson('/api/v1/public/tickets/portal/tickets?token='.urlencode($token))->assertOk();
        $this->assertCount(1, $list->json('data'));
        $ticketId = $list->json('data.0.id');

        $detail = $this->getJson("/api/v1/public/tickets/portal/tickets/{$ticketId}?token=".urlencode($token))->assertOk();
        $this->assertSame('Question facturation', $detail->json('data.ticket.subject'));
        // Le message du client, puis la réponse de l'agent IA (actif par
        // défaut) : même mécanique que pour les autres canaux.
        $this->assertCount(2, $detail->json('data.messages'));
        $this->assertSame('visitor', $detail->json('data.messages.0.sender_type'));
        $this->assertSame('ai', $detail->json('data.messages.1.sender_type'));
    }

    #[Test]
    public function le_client_repond_sur_son_ticket_et_cela_rouvre_une_demande_resolue(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->postJson("/api/v1/public/tickets/{$workspace->id}/submissions", [
            'email' => 'client@exemple.fr',
            'subject' => 'Demande',
            'message' => 'Premier message.',
        ])->assertCreated();

        $token = $this->extractToken(
            (string) $this->mailbox()->lastTo('client@exemple.fr', MailTemplate::TicketPortalLink)->data['action_url'],
        );
        $ticketId = $this->getJson('/api/v1/public/tickets/portal/tickets?token='.urlencode($token))->json('data.0.id');

        // L'équipe résout la demande avant la réponse du client.
        $conversationId = Ticket::query()->findOrFail($ticketId)->conversation_id;
        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/conversations/{$conversationId}/status", ['status' => 'resolved'])
            ->assertOk();

        $this->postJson("/api/v1/public/tickets/portal/tickets/{$ticketId}/messages", [
            'token' => $token,
            'body' => 'Toujours pas résolu de mon côté !',
        ])->assertCreated()->assertJsonPath('data.status', 'open');

        $detail = $this->getJson("/api/v1/public/tickets/portal/tickets/{$ticketId}?token=".urlencode($token))->assertOk();
        // Message initial + réponse IA, puis la réponse du client qui rouvre
        // + une nouvelle réponse IA.
        $this->assertCount(4, $detail->json('data.messages'));
    }

    #[Test]
    public function un_jeton_invalide_est_refuse(): void
    {
        $this->getJson('/api/v1/public/tickets/portal/tickets?token=invalide')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'ticket_portal_token_invalid');
    }

    #[Test]
    public function reemettre_le_lien_repond_204_que_l_adresse_soit_connue_ou_non(): void
    {
        $workspace = Workspace::factory()->create();

        $this->postJson("/api/v1/public/tickets/{$workspace->id}/portal-links", ['email' => 'inconnu@exemple.fr'])
            ->assertStatus(204);

        $this->assertSame([], $this->mailbox()->sent());

        $this->postJson("/api/v1/public/tickets/{$workspace->id}/submissions", [
            'email' => 'client@exemple.fr',
            'subject' => 'Demande',
            'message' => 'Premier message.',
        ])->assertCreated();

        $this->postJson("/api/v1/public/tickets/{$workspace->id}/portal-links", ['email' => 'client@exemple.fr'])
            ->assertStatus(204);

        $this->assertCount(2, $this->mailbox()->sent());
    }
}
