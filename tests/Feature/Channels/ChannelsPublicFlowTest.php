<?php

namespace Tests\Feature\Channels;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelsPublicFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_e_mail_entrant_cree_une_conversation_et_rattache_le_contact(): void
    {
        $workspace = Workspace::factory()->create();

        $response = $this->postJson("/api/v1/public/channels/email/{$workspace->id}/inbound", [
            'from_email' => 'client@exemple.fr',
            'from_name' => 'Client Exemple',
            'body' => "Bonjour, j'ai une question sur ma commande.",
        ])->assertCreated();

        $conversationId = $response->json('data.conversation_id');
        $conversation = Conversation::query()->findOrFail($conversationId);

        $this->assertSame('email', $conversation->channel->value);
        $this->assertNotNull($conversation->contact_id);

        $contact = Contact::query()->where('workspace_id', $workspace->id)->first();
        $this->assertNotNull($contact);
        $this->assertSame('client@exemple.fr', $contact->email);
        $this->assertSame($contact->id, $conversation->contact_id);
    }

    #[Test]
    public function un_second_e_mail_de_la_meme_adresse_reutilise_la_conversation_ouverte(): void
    {
        $workspace = Workspace::factory()->create();

        $first = $this->postJson("/api/v1/public/channels/email/{$workspace->id}/inbound", [
            'from_email' => 'client@exemple.fr',
            'body' => 'Premier message.',
        ])->assertCreated();

        $second = $this->postJson("/api/v1/public/channels/email/{$workspace->id}/inbound", [
            'from_email' => 'client@exemple.fr',
            'body' => 'Deuxième message.',
        ])->assertCreated();

        $this->assertSame($first->json('data.conversation_id'), $second->json('data.conversation_id'));
    }

    #[Test]
    public function le_formulaire_de_contact_cree_une_conversation_sur_son_propre_canal(): void
    {
        $workspace = Workspace::factory()->create();

        $response = $this->postJson("/api/v1/public/channels/contact-form/{$workspace->id}/submissions", [
            'email' => 'visiteur@exemple.fr',
            'name' => 'Visiteur Exemple',
            'message' => 'Je souhaite en savoir plus sur votre offre.',
        ])->assertCreated();

        $conversation = Conversation::query()->findOrFail($response->json('data.conversation_id'));

        $this->assertSame('contact_form', $conversation->channel->value);
    }

    #[Test]
    public function une_reponse_d_agent_sur_une_conversation_e_mail_est_relayee_quand_le_canal_est_actif(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/email/settings', [
                'enabled' => true,
                'reply_from_email' => 'support@exemple.fr',
                'reply_from_name' => 'Support Exemple',
            ])->assertOk();

        $inbound = $this->postJson("/api/v1/public/channels/email/{$workspace->id}/inbound", [
            'from_email' => 'client@exemple.fr',
            'body' => 'Bonjour.',
        ])->assertCreated();

        $conversationId = $inbound->json('data.conversation_id');

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversationId}/messages", [
                'body' => 'Bonjour, voici la réponse à votre question.',
                'visibility' => 'public',
            ])->assertCreated();

        $sent = $this->emailChannelProvider()->sent();
        $this->assertCount(1, $sent);
        $this->assertSame('client@exemple.fr', $sent[0]['to']);
        $this->assertSame('support@exemple.fr', $sent[0]['from_email']);
    }

    #[Test]
    public function une_reponse_d_agent_n_est_pas_relayee_quand_le_canal_e_mail_est_desactive(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $inbound = $this->postJson("/api/v1/public/channels/email/{$workspace->id}/inbound", [
            'from_email' => 'client@exemple.fr',
            'body' => 'Bonjour.',
        ])->assertCreated();

        $conversationId = $inbound->json('data.conversation_id');

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversationId}/messages", [
                'body' => 'Réponse.',
                'visibility' => 'public',
            ])->assertCreated();

        $this->assertSame([], $this->emailChannelProvider()->sent());
    }
}
