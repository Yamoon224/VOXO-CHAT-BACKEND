<?php

namespace Tests\Unit\Assistant;

use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Assistant\Providers\ArrayAiProvider;
use App\Domains\Assistant\Services\AssistantService;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Domains\Knowledge\Contracts\KnowledgeSearchContract;
use App\Domains\Knowledge\DTOs\KnowledgeSearchResult;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryAssistantSettingsRepository;
use Tests\Support\Fakes\InMemoryConversationRepository;
use Tests\Support\Fakes\InMemoryMessageRepository;
use Tests\Support\Fakes\InMemoryQuotaGuard;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class AssistantServiceTest extends TestCase
{
    /** @param  list<KnowledgeSearchResult>  $results */
    private function knowledgeSearch(array $results = []): KnowledgeSearchContract
    {
        return new class($results) implements KnowledgeSearchContract
        {
            /** @param  list<KnowledgeSearchResult>  $results */
            public function __construct(private readonly array $results) {}

            public function search(string $workspaceId, string $query, int $limit = 5): array
            {
                return $this->results;
            }
        };
    }

    #[Test]
    public function une_confiance_suffisante_publie_la_reponse_avec_ses_citations(): void
    {
        $workspace = ModelFactory::workspace();
        $conversation = ModelFactory::conversation(['workspace_id' => $workspace->id]);
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $messages->create(['conversation_id' => $conversation->id, 'workspace_id' => $workspace->id, 'sender_type' => 'visitor', 'body' => 'Quel est le prix ?']);

        $ai = new ArrayAiProvider;
        $ai->respondWith(new AiReply('Le prix est de 29€ par mois.', [['title' => 'Tarifs', 'content' => '29€/mois', 'citation_url' => null]], 0.9));

        $quotas = new InMemoryQuotaGuard;

        $service = new AssistantService(
            $this->knowledgeSearch([new KnowledgeSearchResult('doc-1', 'Tarifs', '29€/mois', 0.8, null)]),
            $ai,
            new InMemoryAssistantSettingsRepository,
            $conversations,
            $messages,
            $quotas,
        );

        $service->processConversationResponse($conversation->id);

        $thread = $messages->forConversation($conversation->id);
        $this->assertCount(2, $thread);
        $this->assertSame('ai', $thread->last()->sender_type->value);
        $this->assertSame('Le prix est de 29€ par mois.', $thread->last()->body);
        $this->assertFalse($conversation->needs_human);
        $this->assertCount(1, $quotas->consumed);
    }

    #[Test]
    public function un_quota_de_credits_ia_epuise_escalade_sans_appeler_le_fournisseur_dia(): void
    {
        $workspace = ModelFactory::workspace();
        $conversation = ModelFactory::conversation(['workspace_id' => $workspace->id]);
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $messages->create(['conversation_id' => $conversation->id, 'workspace_id' => $workspace->id, 'sender_type' => 'visitor', 'body' => 'Quel est le prix ?']);

        $ai = new ArrayAiProvider;
        $quotas = new InMemoryQuotaGuard;
        $quotas->allowAiCredits = false;

        $service = new AssistantService(
            $this->knowledgeSearch(),
            $ai,
            new InMemoryAssistantSettingsRepository,
            $conversations,
            $messages,
            $quotas,
        );

        $service->processConversationResponse($conversation->id);

        $this->assertTrue($conversation->needs_human);
        $this->assertSame([], $ai->calls());
        $this->assertSame([], $quotas->consumed);
    }

    #[Test]
    public function une_confiance_insuffisante_escalade_sans_publier_la_reponse(): void
    {
        $workspace = ModelFactory::workspace();
        $conversation = ModelFactory::conversation(['workspace_id' => $workspace->id]);
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $messages->create(['conversation_id' => $conversation->id, 'workspace_id' => $workspace->id, 'sender_type' => 'visitor', 'body' => 'Question difficile ?']);

        $ai = new ArrayAiProvider;
        $ai->respondWith(new AiReply('Je ne sais pas trop.', [], 0.1));

        $service = new AssistantService(
            $this->knowledgeSearch(),
            $ai,
            new InMemoryAssistantSettingsRepository,
            $conversations,
            $messages,
            new InMemoryQuotaGuard,
        );

        $service->processConversationResponse($conversation->id);

        $this->assertTrue($conversation->needs_human);
        $this->assertSame(ConversationStatus::Pending, $conversation->status);
        $this->assertSame('system', $messages->forConversation($conversation->id)->last()->sender_type->value);
    }

    #[Test]
    public function un_agent_desactive_ne_repond_pas(): void
    {
        $workspace = ModelFactory::workspace();
        $conversation = ModelFactory::conversation(['workspace_id' => $workspace->id]);
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $messages->create(['conversation_id' => $conversation->id, 'workspace_id' => $workspace->id, 'sender_type' => 'visitor', 'body' => 'Bonjour ?']);

        $settings = new InMemoryAssistantSettingsRepository;
        $settings->update($settings->findOrCreateForWorkspace($workspace->id), ['enabled' => false]);

        $ai = new ArrayAiProvider;

        $service = new AssistantService($this->knowledgeSearch(), $ai, $settings, $conversations, $messages, new InMemoryQuotaGuard);

        $service->processConversationResponse($conversation->id);

        $this->assertCount(1, $messages->forConversation($conversation->id));
        $this->assertSame([], $ai->calls());
    }

    #[Test]
    public function le_bac_a_sable_ne_touche_a_aucune_conversation(): void
    {
        $workspace = ModelFactory::workspace();
        $ai = new ArrayAiProvider;
        $ai->respondWith(new AiReply('Réponse de test.', [], 0.95));

        $service = new AssistantService(
            $this->knowledgeSearch(),
            $ai,
            new InMemoryAssistantSettingsRepository,
            new InMemoryConversationRepository,
            new InMemoryMessageRepository,
            new InMemoryQuotaGuard,
        );

        $reply = $service->sandboxRespond($workspace->id, 'Une question de test ?');

        $this->assertSame('Réponse de test.', $reply->content);
        $this->assertSame(0.95, $reply->confidence);
    }

    #[Test]
    public function resumer_et_analyser_le_sentiment_mettent_a_jour_la_conversation(): void
    {
        $workspace = ModelFactory::workspace();
        $conversation = ModelFactory::conversation(['workspace_id' => $workspace->id]);
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $messages->create(['conversation_id' => $conversation->id, 'workspace_id' => $workspace->id, 'sender_type' => 'visitor', 'body' => 'Je suis frustré.']);

        $ai = new ArrayAiProvider;
        $ai->respondWithSummary('Le visiteur se plaint d\'un retard.');
        $ai->respondWithSentiment('négatif');

        $service = new AssistantService($this->knowledgeSearch(), $ai, new InMemoryAssistantSettingsRepository, $conversations, $messages, new InMemoryQuotaGuard);

        $summarized = $service->summarize($workspace->id, $conversation->id);
        $analyzed = $service->analyzeSentiment($workspace->id, $conversation->id);

        $this->assertSame('Le visiteur se plaint d\'un retard.', $summarized->summary);
        $this->assertSame('négatif', $analyzed->sentiment);
    }
}
