<?php

namespace Tests\Feature\Campaigns;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\Contact;
use App\Models\ContactSegment;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignSendFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_campagne_e_mail_est_livree_a_chaque_contact_du_segment(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id, 'criteria' => []]);
        $template = CampaignTemplate::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'email',
            'subject' => 'Bonjour {{name}}',
            'body' => 'Contenu pour {{email}}.',
        ]);
        $contact = Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Awa', 'email' => 'awa@exemple.fr']);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'email',
            'segment_id' => $segment->id,
            'template_id' => $template->id,
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")
            ->assertOk()
            ->assertJsonPath('data.status', 'sent');

        $this->assertSame(1, $response->json('data.stats.sent'));

        $sentRaw = $this->mailbox()->sentRaw();
        $this->assertCount(1, $sentRaw);
        $this->assertSame($contact->email, $sentRaw[0]['to']);
        $this->assertSame('Bonjour Awa', $sentRaw[0]['subject']);
        $this->assertSame('Contenu pour awa@exemple.fr.', $sentRaw[0]['bodyHtml']);
    }

    #[Test]
    public function un_nom_de_contact_avec_du_html_est_echappe_dans_le_corps_e_mail(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id, 'criteria' => []]);
        $template = CampaignTemplate::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'email',
            'subject' => 'Bonjour {{name}}',
            'body' => 'Bonjour {{name}}.',
        ]);
        Contact::factory()->create([
            'workspace_id' => $workspace->id,
            'name' => '<img src=x onerror=alert(1)>',
            'email' => 'visiteur@exemple.fr',
        ]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'email',
            'segment_id' => $segment->id,
            'template_id' => $template->id,
        ]);

        $this->actingInWorkspace($admin, $workspace)->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")->assertOk();

        $sentRaw = $this->mailbox()->sentRaw();
        $this->assertCount(1, $sentRaw);
        $this->assertStringNotContainsString('<img', $sentRaw[0]['bodyHtml']);
        $this->assertStringContainsString('&lt;img', $sentRaw[0]['bodyHtml']);
    }

    #[Test]
    public function un_contact_sans_adresse_e_mail_est_ignore(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id, 'criteria' => []]);
        $template = CampaignTemplate::factory()->create(['workspace_id' => $workspace->id, 'channel' => 'email']);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'email' => null]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'email',
            'segment_id' => $segment->id,
            'template_id' => $template->id,
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")
            ->assertOk();

        $this->assertSame(1, $response->json('data.stats.skipped'));
        $this->assertCount(0, $this->mailbox()->sentRaw());
    }

    #[Test]
    public function une_campagne_chat_poste_un_message_systeme_dans_la_conversation_du_contact(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id, 'criteria' => []]);
        $template = CampaignTemplate::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'chat',
            'subject' => null,
            'body' => 'Bonjour {{name}}, nouveauté chez nous !',
        ]);
        $contact = Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Awa']);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id, 'contact_id' => $contact->id]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'chat',
            'segment_id' => $segment->id,
            'template_id' => $template->id,
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")
            ->assertOk();

        $this->assertSame(1, $response->json('data.stats.sent'));
        $message = $conversation->messages()->latest('created_at')->first();
        $this->assertNotNull($message);
        $this->assertSame('system', $message->sender_type->value);
        $this->assertSame('Bonjour Awa, nouveauté chez nous !', $message->body);
    }

    #[Test]
    public function un_contact_chat_sans_conversation_est_ignore(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id, 'criteria' => []]);
        $template = CampaignTemplate::factory()->create(['workspace_id' => $workspace->id, 'channel' => 'chat', 'subject' => null]);
        Contact::factory()->create(['workspace_id' => $workspace->id]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'chat',
            'segment_id' => $segment->id,
            'template_id' => $template->id,
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")
            ->assertOk();

        $this->assertSame(1, $response->json('data.stats.skipped'));
    }

    #[Test]
    public function une_campagne_deja_envoyee_ne_peut_pas_etre_renvoyee(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $campaign = Campaign::factory()->create(['workspace_id' => $workspace->id, 'status' => 'sent']);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'campaign_already_sent');
    }

    #[Test]
    public function un_agent_ne_peut_pas_envoyer_de_campagne(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $campaign = Campaign::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")
            ->assertStatus(403);
    }

    #[Test]
    public function le_detail_des_envois_liste_chaque_contact(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $segment = ContactSegment::factory()->create(['workspace_id' => $workspace->id, 'criteria' => []]);
        $template = CampaignTemplate::factory()->create(['workspace_id' => $workspace->id, 'channel' => 'email']);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Awa', 'email' => 'awa@exemple.fr']);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'email',
            'segment_id' => $segment->id,
            'template_id' => $template->id,
        ]);

        $this->actingInWorkspace($admin, $workspace)->postJson("/api/v1/workspace/campaigns/{$campaign->id}/send")->assertOk();

        $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/campaigns/{$campaign->id}/sends")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.contact_name', 'Awa')
            ->assertJsonPath('data.0.status', 'sent');
    }
}
