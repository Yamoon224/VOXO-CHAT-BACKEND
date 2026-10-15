<?php

namespace Tests\Feature\Automations;

use App\Domains\Assistant\DTOs\AiReply;
use App\Models\AutomationScenario;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutomationExecutionFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_scenario_publie_repond_a_l_ouverture_sans_appeler_l_agent_ia(): void
    {
        $workspace = Workspace::factory()->create();
        AutomationScenario::factory()->create([
            'workspace_id' => $workspace->id,
            'status' => 'published',
            'trigger_type' => 'conversation_opened',
            'conditions' => [],
            'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bienvenue chez nous !']]],
        ]);
        $this->aiProvider()->respondWith(new AiReply('Je ne devrais jamais être appelé.', [], 0.9));

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'Bonjour.'])->assertCreated();

        $messages = $this->getJson('/api/v1/public/widget/messages?token='.urlencode($token))->assertOk();

        $this->assertCount(2, $messages->json('data'));
        $this->assertSame('visitor', $messages->json('data.0.sender_type'));
        $this->assertSame('system', $messages->json('data.1.sender_type'));
        $this->assertSame('Bienvenue chez nous !', $messages->json('data.1.body'));
    }

    #[Test]
    public function un_scenario_avec_une_question_collecte_la_reponse_sur_le_contact(): void
    {
        $workspace = Workspace::factory()->create();
        AutomationScenario::factory()->create([
            'workspace_id' => $workspace->id,
            'status' => 'published',
            'trigger_type' => 'conversation_opened',
            'conditions' => [],
            'steps' => [
                ['type' => 'ask_question', 'config' => ['body' => 'Quel est votre e-mail ?', 'attribute_key' => 'email']],
                ['type' => 'send_message', 'config' => ['body' => 'Merci, un agent vous répondra.']],
            ],
        ]);

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'Bonjour.'])->assertCreated();
        $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'client@exemple.fr'])->assertCreated();

        $messages = $this->getJson('/api/v1/public/widget/messages?token='.urlencode($token))->assertOk();
        $bodies = array_column($messages->json('data'), 'body');

        $this->assertContains('Quel est votre e-mail ?', $bodies);
        $this->assertContains('Merci, un agent vous répondra.', $bodies);

        $contact = Contact::query()->where('workspace_id', $workspace->id)->first();
        $this->assertNotNull($contact);
        $this->assertSame('client@exemple.fr', $contact->custom_attributes['email']);
    }
}
