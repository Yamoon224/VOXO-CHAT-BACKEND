<?php

namespace App\Domains\Assistant\Services;

use App\Domains\Assistant\Contracts\AiProviderContract;
use App\Domains\Assistant\Contracts\AssistantResponderContract;
use App\Domains\Assistant\Contracts\AssistantSettingsRepositoryContract;
use App\Domains\Assistant\Contracts\ConversationInsightContract;
use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Assistant\Jobs\RespondToVisitorMessageJob;
use App\Domains\Billing\Contracts\QuotaGuardContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeSearchContract;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;

/**
 * Orchestration de l'agent IA : cherche dans la base de connaissances,
 * demande une réponse au fournisseur d'IA, et selon la confiance obtenue,
 * répond au visiteur ou passe la main à un humain avec le contexte complet
 * (section 2.2 du cahier des charges).
 */
final class AssistantService implements AssistantResponderContract, ConversationInsightContract
{
    private const BASE_PROMPT = "Tu es l'agent de support de cette entreprise.";

    private const DEFAULT_TONE = 'Réponds de façon professionnelle, concise et chaleureuse.';

    private const ESCALATION_MESSAGE = 'Je ne suis pas certain de pouvoir répondre avec certitude : je transmets votre question à un coéquipier, qui vous répondra dès que possible.';

    private const QUOTA_EXHAUSTED_MESSAGE = 'Le quota de réponses automatiques de ce mois est atteint : je transmets votre question à un coéquipier, qui vous répondra dès que possible.';

    public function __construct(
        private readonly KnowledgeSearchContract $knowledgeSearch,
        private readonly AiProviderContract $ai,
        private readonly AssistantSettingsRepositoryContract $settings,
        private readonly ConversationRepositoryContract $conversations,
        private readonly MessageRepositoryContract $messages,
        private readonly QuotaGuardContract $quotas,
    ) {}

    /** Implémente `AssistantResponderContract` : déclenche la réponse, en file. */
    public function respondToConversation(string $conversationId): void
    {
        RespondToVisitorMessageJob::dispatch($conversationId);
    }

    /** Appelé par `RespondToVisitorMessageJob`, jamais directement par un contrôleur. */
    public function processConversationResponse(string $conversationId): void
    {
        $conversation = $this->conversations->findOrFailById($conversationId);
        $settings = $this->settings->findOrCreateForWorkspace($conversation->workspace_id);

        if (! $settings->enabled) {
            return;
        }

        $history = $this->historyFor($conversation->id);
        $lastVisitorMessage = $this->lastVisitorMessageBody($history);

        if ($lastVisitorMessage === null) {
            return;
        }

        if (! $this->quotas->canConsumeAiCredit($conversation->workspace_id)) {
            $this->escalate($conversation, self::QUOTA_EXHAUSTED_MESSAGE);

            return;
        }

        $context = $this->knowledgeContext($conversation->workspace_id, $lastVisitorMessage);
        $reply = $this->ai->respond(
            $this->systemPrompt($settings->tone_instructions),
            $history,
            $context,
        );

        if ($reply->confidence < $settings->confidence_threshold) {
            $this->escalate($conversation, self::ESCALATION_MESSAGE);

            return;
        }

        $this->quotas->consumeAiCredit($conversation->workspace_id, "Réponse de l'agent IA", $conversation->id);
        $this->messages->recordAiReply($conversation->id, $conversation->workspace_id, $reply->content, $reply->citations);

        $this->conversations->update($conversation, ['last_message_at' => now()]);
    }

    /**
     * Aperçu de l'agent IA avant activation (bac à sable, section 2.2) :
     * cherche et répond sans toucher à aucune conversation. Ne consomme pas
     * de crédit IA : un réglage qui teste son agent ne doit pas être compté
     * comme du trafic client.
     */
    public function sandboxRespond(string $workspaceId, string $message): AiReply
    {
        $settings = $this->settings->findOrCreateForWorkspace($workspaceId);
        $context = $this->knowledgeContext($workspaceId, $message);

        return $this->ai->respond(
            $this->systemPrompt($settings->tone_instructions),
            [['role' => 'user', 'content' => $message]],
            $context,
        );
    }

    public function summarize(string $workspaceId, string $conversationId): Conversation
    {
        $conversation = $this->conversations->findInWorkspaceOrFail($workspaceId, $conversationId);
        $summary = $this->ai->summarize($this->historyFor($conversation->id));

        return $this->conversations->update($conversation, ['summary' => $summary]);
    }

    public function analyzeSentiment(string $workspaceId, string $conversationId): Conversation
    {
        $conversation = $this->conversations->findInWorkspaceOrFail($workspaceId, $conversationId);
        $sentiment = $this->ai->analyzeSentiment($this->historyFor($conversation->id));

        return $this->conversations->update($conversation, ['sentiment' => $sentiment]);
    }

    private function escalate(Conversation $conversation, string $message): void
    {
        $this->messages->recordSystemNotice($conversation->id, $conversation->workspace_id, $message);
        $this->conversations->escalateToHuman($conversation);
    }

    /**
     * Historique donné à l'IA : messages visibles du visiteur uniquement —
     * les notes internes de l'équipe ne doivent jamais se retrouver dans un
     * prompt ni dans un résumé.
     *
     * @return list<array{role: 'user'|'assistant', content: string}>
     */
    private function historyFor(string $conversationId): array
    {
        return $this->messages->publicForConversation($conversationId)
            ->map(fn (Message $message) => [
                'role' => $message->isFromVisitor() ? 'user' : 'assistant',
                'content' => $message->body,
            ])
            ->all();
    }

    /** @param  list<array{role: 'user'|'assistant', content: string}>  $history */
    private function lastVisitorMessageBody(array $history): ?string
    {
        $visitorMessages = array_values(array_filter($history, fn (array $message) => $message['role'] === 'user'));

        return $visitorMessages === [] ? null : (string) end($visitorMessages)['content'];
    }

    /** @return list<array{title: string, content: string, citation_url: string|null}> */
    private function knowledgeContext(string $workspaceId, string $query): array
    {
        return (new Collection($this->knowledgeSearch->search($workspaceId, $query)))
            ->map(fn ($result) => [
                'title' => $result->documentTitle,
                'content' => $result->chunkContent,
                'citation_url' => $result->citationUrl,
            ])
            ->all();
    }

    private function systemPrompt(?string $toneInstructions): string
    {
        return trim(self::BASE_PROMPT.' '.($toneInstructions ?: self::DEFAULT_TONE));
    }
}
