<?php

namespace Tests\Feature\Widget;

use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Realtime\Providers\ArrayConversationBroadcaster;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WidgetPublicFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_visiteur_ouvre_une_session_et_recoit_la_reponse_de_l_agent_ia(): void
    {
        $workspace = Workspace::factory()->create();

        $this->aiProvider()->respondWith(new AiReply('Nous sommes ouverts de 9h à 18h.', [], 0.95));

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $this->postJson('/api/v1/public/widget/messages', [
            'token' => $token,
            'body' => 'Quels sont vos horaires ?',
        ])->assertCreated();

        $messages = $this->getJson('/api/v1/public/widget/messages?token='.urlencode($token))->assertOk();

        $this->assertCount(2, $messages->json('data'));
        $this->assertSame('visitor', $messages->json('data.0.sender_type'));
        $this->assertSame('ai', $messages->json('data.1.sender_type'));
        $this->assertSame('Nous sommes ouverts de 9h à 18h.', $messages->json('data.1.body'));
    }

    #[Test]
    public function le_sondage_expose_l_identifiant_de_conversation_pour_s_abonner_au_temps_reel(): void
    {
        $workspace = Workspace::factory()->create();
        $this->aiProvider()->respondWith(new AiReply('Entendu.', [], 0.95));

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');
        $sent = $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'Bonjour.'])->assertCreated();

        $messages = $this->getJson('/api/v1/public/widget/messages?token='.urlencode($token))->assertOk();

        $this->assertSame($sent->json('data.conversation_id'), $messages->json('meta.conversation_id'));
    }

    #[Test]
    public function un_message_visiteur_et_la_reponse_ia_sont_diffuses_en_temps_reel(): void
    {
        $workspace = Workspace::factory()->create();
        $this->aiProvider()->respondWith(new AiReply('Entendu.', [], 0.95));

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'Bonjour.'])->assertCreated();

        $broadcasted = app(ArrayConversationBroadcaster::class)->broadcasted();
        $this->assertCount(2, $broadcasted);
        $this->assertTrue($broadcasted[0]->isVisibleToVisitor());
        $this->assertSame('visitor', $broadcasted[0]->sender_type->value);
        $this->assertTrue($broadcasted[1]->isVisibleToVisitor());
        $this->assertSame('ai', $broadcasted[1]->sender_type->value);
    }

    #[Test]
    public function un_jeton_invalide_est_refuse(): void
    {
        $this->getJson('/api/v1/public/widget/messages?token=invalide')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'visitor_session_invalid');
    }

    #[Test]
    public function un_visiteur_note_sa_conversation(): void
    {
        $workspace = Workspace::factory()->create();
        $this->aiProvider()->respondWith(new AiReply('Bien sûr !', [], 0.95));

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $sent = $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'Merci !'])->assertCreated();

        $this->postJson('/api/v1/public/widget/ratings', [
            'token' => $token,
            'conversation_id' => $sent->json('data.conversation_id'),
            'rating' => 5,
        ])->assertStatus(204);
    }

    #[Test]
    public function les_reglages_publics_du_widget_sont_lisibles_sans_authentification(): void
    {
        $workspace = Workspace::factory()->create();

        $this->getJson("/api/v1/public/widget/{$workspace->id}/settings")
            ->assertOk()
            ->assertJsonStructure(['data' => ['primary_color', 'position', 'language', 'is_open_now', 'hide_branding', 'realtime']])
            // Aucun serveur Reverb configuré en test (`BROADCAST_CONNECTION=null`) :
            // le widget doit retomber sur le sondage plutôt que d'essayer de
            // se connecter à un serveur inexistant.
            ->assertJsonPath('data.realtime', null);
    }

    #[Test]
    public function chaque_lecture_publique_des_reglages_vaut_verification_d_installation(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $before = $this->actingInWorkspace($admin, $workspace)->getJson('/api/v1/workspace/widget/settings')->assertOk();
        $this->assertNull($before->json('data.last_installed_check_at'));

        $this->getJson("/api/v1/public/widget/{$workspace->id}/settings")->assertOk();

        $after = $this->actingInWorkspace($admin, $workspace)->getJson('/api/v1/workspace/widget/settings')->assertOk();
        $this->assertNotNull($after->json('data.last_installed_check_at'));
    }
}
