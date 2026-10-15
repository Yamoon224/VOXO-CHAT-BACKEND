<?php

namespace Tests\Feature\Channels;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OmnicanalChannelsFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_message_whatsapp_entrant_cree_une_conversation_et_rattache_le_contact_par_telephone(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $appSecret = $this->enableWhatsApp($workspace, $admin);
        $payload = $this->whatsAppPayload('33612345678', 'Bonjour.');

        $response = $this->postJson("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound", $payload, $this->metaSignatureHeader($payload, $appSecret))
            ->assertCreated();

        $conversation = Conversation::query()->findOrFail($response->json('data.conversation_id'));
        $this->assertSame('whatsapp', $conversation->channel->value);
        $this->assertSame('33612345678', $conversation->visitor_id);

        $contact = Contact::query()->where('workspace_id', $workspace->id)->first();
        $this->assertSame('33612345678', $contact->phone);
        $this->assertSame($contact->id, $conversation->contact_id);
    }

    #[Test]
    public function un_second_message_whatsapp_du_meme_numero_reutilise_la_conversation_ouverte(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $appSecret = $this->enableWhatsApp($workspace, $admin);

        $firstPayload = $this->whatsAppPayload('33612345678', 'Premier message.');
        $first = $this->postJson("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound", $firstPayload, $this->metaSignatureHeader($firstPayload, $appSecret))
            ->assertCreated();

        $secondPayload = $this->whatsAppPayload('33612345678', 'Deuxième message.');
        $second = $this->postJson("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound", $secondPayload, $this->metaSignatureHeader($secondPayload, $appSecret))
            ->assertCreated();

        $this->assertSame($first->json('data.conversation_id'), $second->json('data.conversation_id'));
    }

    #[Test]
    public function un_message_whatsapp_sans_canal_active_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $payload = $this->whatsAppPayload('33612345678', 'Bonjour.');

        $this->postJson("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound", $payload, $this->metaSignatureHeader($payload, 'peu importe'))
            ->assertStatus(403);
    }

    #[Test]
    public function un_message_whatsapp_avec_une_signature_invalide_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $this->enableWhatsApp($workspace, $admin);
        $payload = $this->whatsAppPayload('33612345678', 'Bonjour.');

        $this->postJson("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound", $payload, $this->metaSignatureHeader($payload, 'mauvais-secret'))
            ->assertStatus(403);
    }

    #[Test]
    public function un_message_messenger_entrant_rattache_le_contact_par_psid(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $appSecret = 'secret-messenger';
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/messenger/connection', [
                'credentials' => ['page_access_token' => 'page-token', 'app_secret' => $appSecret],
            ])->assertOk();
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/messenger/connection', ['enabled' => true])
            ->assertOk();

        $payload = ['entry' => [['messaging' => [['sender' => ['id' => 'psid-123'], 'message' => ['text' => 'Salut !']]]]]];
        $response = $this->postJson("/api/v1/public/channels/messenger/{$workspace->id}/inbound", $payload, $this->metaSignatureHeader($payload, $appSecret))
            ->assertCreated();

        $conversation = Conversation::query()->findOrFail($response->json('data.conversation_id'));
        $this->assertSame('messenger', $conversation->channel->value);

        $contact = Contact::query()->where('workspace_id', $workspace->id)->first();
        $this->assertSame('psid-123', $contact->custom_attributes['messenger_id']);
    }

    #[Test]
    public function un_message_instagram_entrant_rattache_le_contact_par_igsid(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $appSecret = 'secret-instagram';
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/instagram/connection', [
                'credentials' => ['page_access_token' => 'page-token', 'app_secret' => $appSecret],
            ])->assertOk();
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/instagram/connection', ['enabled' => true])
            ->assertOk();

        $payload = ['entry' => [['messaging' => [['sender' => ['id' => 'igsid-456'], 'message' => ['text' => 'Coucou']]]]]];
        $response = $this->postJson("/api/v1/public/channels/instagram/{$workspace->id}/inbound", $payload, $this->metaSignatureHeader($payload, $appSecret))
            ->assertCreated();

        $conversation = Conversation::query()->findOrFail($response->json('data.conversation_id'));
        $this->assertSame('instagram', $conversation->channel->value);

        $contact = Contact::query()->where('workspace_id', $workspace->id)->first();
        $this->assertSame('igsid-456', $contact->custom_attributes['instagram_id']);
    }

    #[Test]
    public function un_message_telegram_entrant_rattache_le_contact_par_identifiant_de_chat(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $secretToken = $this->enableTelegram($workspace, $admin);

        $response = $this->postJson("/api/v1/public/channels/telegram/{$workspace->id}/inbound", [
            'message' => ['chat' => ['id' => 987654], 'from' => ['first_name' => 'Alex'], 'text' => 'Bonjour le bot.'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => $secretToken])->assertCreated();

        $conversation = Conversation::query()->findOrFail($response->json('data.conversation_id'));
        $this->assertSame('telegram', $conversation->channel->value);
        $this->assertSame('987654', $conversation->visitor_id);
        $this->assertSame('Alex', $conversation->visitor_name);
    }

    #[Test]
    public function un_message_telegram_avec_un_jeton_secret_invalide_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $this->enableTelegram($workspace, $admin);

        $this->postJson("/api/v1/public/channels/telegram/{$workspace->id}/inbound", [
            'message' => ['chat' => ['id' => 987654], 'text' => 'Bonjour.'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'mauvais-jeton'])->assertStatus(403);
    }

    #[Test]
    public function un_sms_entrant_rattache_le_contact_par_telephone(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $authToken = $this->enableSms($workspace, $admin);
        $params = ['From' => '+33612345678', 'Body' => 'Bonjour par SMS.'];

        $response = $this->post(
            "/api/v1/public/channels/sms/{$workspace->id}/inbound",
            $params,
            $this->twilioSignatureHeader($workspace->id, $params, $authToken),
        )->assertCreated();

        $conversation = Conversation::query()->findOrFail($response->json('data.conversation_id'));
        $this->assertSame('sms', $conversation->channel->value);

        $contact = Contact::query()->where('workspace_id', $workspace->id)->first();
        $this->assertSame('+33612345678', $contact->phone);
    }

    #[Test]
    public function un_sms_avec_une_signature_invalide_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $this->enableSms($workspace, $admin);
        $params = ['From' => '+33612345678', 'Body' => 'Bonjour par SMS.'];

        $this->post(
            "/api/v1/public/channels/sms/{$workspace->id}/inbound",
            $params,
            ['X-Twilio-Signature' => 'signature-invalide'],
        )->assertStatus(403);
    }

    #[Test]
    public function la_poignee_de_main_whatsapp_reussit_avec_le_bon_jeton(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $verifyToken = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/channels/whatsapp/connection')
            ->json('data.credentials.verify_token');

        $this->get("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound?hub.mode=subscribe&hub.verify_token={$verifyToken}&hub.challenge=42")
            ->assertOk()
            ->assertSee('42');
    }

    #[Test]
    public function la_poignee_de_main_whatsapp_echoue_avec_un_mauvais_jeton(): void
    {
        $workspace = Workspace::factory()->create();

        $this->get("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound?hub.mode=subscribe&hub.verify_token=mauvais&hub.challenge=42")
            ->assertStatus(403);
    }

    #[Test]
    public function une_reponse_d_agent_est_relayee_au_canal_whatsapp_quand_il_est_actif(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $appSecret = $this->enableWhatsApp($workspace, $admin);

        $payload = $this->whatsAppPayload('33612345678', 'Bonjour.');
        $inbound = $this->postJson("/api/v1/public/channels/whatsapp/{$workspace->id}/inbound", $payload, $this->metaSignatureHeader($payload, $appSecret))
            ->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$inbound->json('data.conversation_id')}/messages", [
                'body' => 'Voici la réponse.',
                'visibility' => 'public',
            ])->assertCreated();

        $sent = $this->whatsAppChannelProvider()->sent();
        $this->assertCount(1, $sent);
        $this->assertSame('33612345678', $sent[0]['to']);
        $this->assertSame('Voici la réponse.', $sent[0]['body']);
    }

    #[Test]
    public function une_reponse_d_agent_n_est_pas_relayee_quand_le_canal_whatsapp_n_est_pas_configure(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        // Le canal n'étant pas configuré, le webhook public le refuserait
        // (403) — on simule directement l'état « conversation whatsapp déjà
        // ouverte » pour isoler le comportement testé : le relais sortant.
        $contact = Contact::factory()->create(['workspace_id' => $workspace->id, 'phone' => '33612345678']);
        $conversation = Conversation::factory()->create([
            'workspace_id' => $workspace->id,
            'channel' => 'whatsapp',
            'visitor_id' => '33612345678',
            'contact_id' => $contact->id,
        ]);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/messages", [
                'body' => 'Réponse.',
                'visibility' => 'public',
            ])->assertCreated();

        $this->assertSame([], $this->whatsAppChannelProvider()->sent());
    }

    /** @return array<string, mixed> */
    private function whatsAppPayload(string $from, string $body): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [['from' => $from, 'text' => ['body' => $body]]],
                        'contacts' => [['profile' => ['name' => 'Client WhatsApp']]],
                    ],
                ]],
            ]],
        ];
    }

    /** Active WhatsApp pour l'espace et renvoie l'`app_secret` utilisé pour signer les webhooks de test. */
    private function enableWhatsApp(Workspace $workspace, User $admin): string
    {
        $appSecret = 'app-secret-whatsapp';
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/whatsapp/connection', [
                'credentials' => ['access_token' => 'token-abc', 'phone_number_id' => '1234567890', 'app_secret' => $appSecret],
            ])->assertOk();
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/whatsapp/connection', ['enabled' => true])
            ->assertOk();

        return $appSecret;
    }

    /** Active Telegram pour l'espace et renvoie le `secret_token` engendré à la création de la connexion. */
    private function enableTelegram(Workspace $workspace, User $admin): string
    {
        $secretToken = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/channels/telegram/connection')
            ->json('data.credentials.secret_token');

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/telegram/connection', ['credentials' => ['bot_token' => '123456:abcdefgh']])
            ->assertOk();
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/telegram/connection', ['enabled' => true])
            ->assertOk();

        return $secretToken;
    }

    /** Active le SMS pour l'espace et renvoie l'`auth_token` utilisé pour signer les webhooks de test. */
    private function enableSms(Workspace $workspace, User $admin): string
    {
        $authToken = 'auth-token-twilio';
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/sms/connection', [
                'credentials' => ['account_sid' => 'AC123', 'auth_token' => $authToken, 'from_number' => '+33600000000'],
            ])->assertOk();
        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/sms/connection', ['enabled' => true])
            ->assertOk();

        return $authToken;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function metaSignatureHeader(array $payload, string $appSecret): array
    {
        return ['X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', (string) json_encode($payload), $appSecret)];
    }

    /**
     * @param  array<string, string>  $params
     * @return array<string, string>
     */
    private function twilioSignatureHeader(string $workspaceId, array $params, string $authToken): array
    {
        $fullUrl = rtrim((string) config('app.url'), '/')."/api/v1/public/channels/sms/{$workspaceId}/inbound";

        ksort($params);
        $data = $fullUrl;
        foreach ($params as $key => $value) {
            $data .= $key.$value;
        }

        return ['X-Twilio-Signature' => base64_encode(hash_hmac('sha1', $data, $authToken, true))];
    }
}
