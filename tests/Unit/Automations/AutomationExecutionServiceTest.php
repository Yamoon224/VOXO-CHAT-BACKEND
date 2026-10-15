<?php

namespace Tests\Unit\Automations;

use App\Domains\Automations\Services\AutomationExecutionService;
use App\Domains\Contacts\Services\ContactService;
use App\Models\AutomationScenario;
use App\Models\Conversation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryAutomationRunRepository;
use Tests\Support\Fakes\InMemoryAutomationScenarioRepository;
use Tests\Support\Fakes\InMemoryContactEventRepository;
use Tests\Support\Fakes\InMemoryContactRepository;
use Tests\Support\Fakes\InMemoryConversationRepository;
use Tests\Support\Fakes\InMemoryMessageRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class AutomationExecutionServiceTest extends TestCase
{
    #[Test]
    public function un_scenario_non_publie_n_est_jamais_declenche(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $scenario = ModelFactory::automationScenario([
            'workspace_id' => 'workspace-1',
            'status' => 'draft',
            'trigger_type' => 'conversation_opened',
            'conditions' => [],
            'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bonjour !']]],
        ]);
        [$service, $messages] = $this->makeService($scenario, $conversation);

        $handled = $service->applyTo($conversation, 'Bonjour.', isNewConversation: true);

        $this->assertFalse($handled);
        $this->assertCount(0, $messages->forConversation($conversation->id));
    }

    #[Test]
    public function un_scenario_a_l_ouverture_envoie_un_message_et_se_termine(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $scenario = ModelFactory::automationScenario([
            'workspace_id' => 'workspace-1',
            'status' => 'published',
            'trigger_type' => 'conversation_opened',
            'conditions' => [],
            'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bienvenue !']]],
        ]);
        [$service, $messages] = $this->makeService($scenario, $conversation);

        $handled = $service->applyTo($conversation, 'Bonjour.', isNewConversation: true);

        $this->assertTrue($handled);
        $sent = $messages->forConversation($conversation->id);
        $this->assertCount(1, $sent);
        $this->assertSame('Bienvenue !', $sent->first()->body);
    }

    #[Test]
    public function un_declencheur_conversation_ouverte_ne_joue_pas_sur_un_message_suivant(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $scenario = ModelFactory::automationScenario([
            'workspace_id' => 'workspace-1',
            'status' => 'published',
            'trigger_type' => 'conversation_opened',
            'conditions' => [],
            'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bienvenue !']]],
        ]);
        [$service] = $this->makeService($scenario, $conversation);

        $handled = $service->applyTo($conversation, 'Un autre message.', isNewConversation: false);

        $this->assertFalse($handled);
    }

    #[Test]
    public function une_etape_poser_une_question_interrompt_puis_reprend_a_la_reponse(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'contact_id' => null]);
        $scenario = ModelFactory::automationScenario([
            'workspace_id' => 'workspace-1',
            'status' => 'published',
            'trigger_type' => 'conversation_opened',
            'conditions' => [],
            'steps' => [
                ['type' => 'ask_question', 'config' => ['body' => 'Quel est votre e-mail ?', 'attribute_key' => 'email']],
                ['type' => 'send_message', 'config' => ['body' => 'Merci !']],
            ],
        ]);
        [$service, $messages, $runs, $contacts, $conversations] = $this->makeService($scenario, $conversation);

        $firstTurn = $service->applyTo($conversation, 'Bonjour.', isNewConversation: true);
        $this->assertTrue($firstTurn);
        $this->assertCount(1, $messages->forConversation($conversation->id));
        $this->assertNotNull($runs->findActiveForConversation($conversation->id));

        $conversation = $conversations->findInWorkspaceOrFail('workspace-1', $conversation->id);
        $secondTurn = $service->applyTo($conversation, 'client@exemple.fr', isNewConversation: false);

        $this->assertTrue($secondTurn);
        $sent = $messages->forConversation($conversation->id);
        $this->assertCount(2, $sent);
        $this->assertSame('Merci !', $sent->last()->body);
        $this->assertNull($runs->findActiveForConversation($conversation->id));

        $contact = $contacts->findInWorkspaceOrFail('workspace-1', $conversations->findInWorkspaceOrFail('workspace-1', $conversation->id)->contact_id);
        $this->assertSame('client@exemple.fr', $contact->custom_attributes['email']);
    }

    #[Test]
    public function un_canal_qui_ne_correspond_pas_a_la_condition_ne_declenche_rien(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'channel' => 'widget']);
        $scenario = ModelFactory::automationScenario([
            'workspace_id' => 'workspace-1',
            'status' => 'published',
            'trigger_type' => 'conversation_opened',
            'conditions' => [['field' => 'channel', 'operator' => 'equals', 'value' => 'email']],
            'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bonjour !']]],
        ]);
        [$service] = $this->makeService($scenario, $conversation);

        $handled = $service->applyTo($conversation, 'Bonjour.', isNewConversation: true);

        $this->assertFalse($handled);
    }

    /** @return array{0: AutomationExecutionService, 1: InMemoryMessageRepository, 2: InMemoryAutomationRunRepository, 3: InMemoryContactRepository, 4: InMemoryConversationRepository} */
    private function makeService(AutomationScenario $scenario, Conversation $conversation): array
    {
        $scenarios = new InMemoryAutomationScenarioRepository($scenario);
        $runs = new InMemoryAutomationRunRepository;
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $contactRepository = new InMemoryContactRepository;
        $contacts = new ContactService($contactRepository, new InMemoryContactEventRepository);

        $service = new AutomationExecutionService($scenarios, $runs, $conversations, $messages, $contacts);

        return [$service, $messages, $runs, $contactRepository, $conversations];
    }
}
