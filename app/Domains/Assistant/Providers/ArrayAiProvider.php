<?php

namespace App\Domains\Assistant\Providers;

use App\Domains\Assistant\Contracts\AiProviderContract;
use App\Domains\Assistant\DTOs\AiReply;

/**
 * Doublure de test : pas d'appel réseau, réponses configurables. Enregistrée
 * comme singleton (voir `DomainServiceProvider`) pour que les tests
 * redemandent exactement l'instance utilisée par l'application.
 */
final class ArrayAiProvider implements AiProviderContract
{
    private ?AiReply $reply = null;

    private string $summary = 'Résumé simulé de la conversation.';

    private string $sentiment = 'neutre';

    /** @var list<array{system: string, history: list<array{role: string, content: string}>, context: list<array{title: string, content: string, citation_url: string|null}>}> */
    private array $calls = [];

    public function respondWith(AiReply $reply): void
    {
        $this->reply = $reply;
    }

    public function respondWithSummary(string $summary): void
    {
        $this->summary = $summary;
    }

    public function respondWithSentiment(string $sentiment): void
    {
        $this->sentiment = $sentiment;
    }

    public function respond(string $systemPrompt, array $conversationHistory, array $knowledgeContext): AiReply
    {
        $this->calls[] = ['system' => $systemPrompt, 'history' => $conversationHistory, 'context' => $knowledgeContext];

        return $this->reply ?? new AiReply('Réponse simulée.', [], 1.0);
    }

    public function summarize(array $conversationHistory): string
    {
        return $this->summary;
    }

    public function analyzeSentiment(array $conversationHistory): string
    {
        return $this->sentiment;
    }

    /** @return list<array{system: string, history: list<array{role: string, content: string}>, context: list<array{title: string, content: string, citation_url: string|null}>}> */
    public function calls(): array
    {
        return $this->calls;
    }
}
