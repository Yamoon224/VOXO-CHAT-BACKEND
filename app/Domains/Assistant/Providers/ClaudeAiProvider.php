<?php

namespace App\Domains\Assistant\Providers;

use App\Domains\Assistant\Contracts\AiProviderContract;
use App\Domains\Assistant\DTOs\AiReply;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Agent IA par l'API Claude (section 4.6) : Sonnet 5.5 pour répondre,
 * Haiku 4.5 pour les tâches courtes.
 *
 * Claude ne renvoie pas nativement de score de confiance : la réponse est
 * demandée en JSON strict (`answer`, `confidence`, `citation_indices`) pour
 * en obtenir un exploitable. Une réponse mal formée retombe sur une
 * confiance nulle — en échec, on escalade plutôt que d'inventer une réponse.
 */
final class ClaudeAiProvider implements AiProviderContract
{
    private const RESPOND_PROMPT = <<<'TEXT'
Réponds uniquement à partir des passages fournis ci-dessous, avec leurs
numéros entre crochets. Si les passages ne permettent pas de répondre avec
certitude, dis-le et baisse ta confiance en conséquence plutôt que d'inventer.

Réponds strictement en JSON, sans aucun texte autour, au format :
{"answer": "...", "confidence": nombre entre 0 et 1, "citation_indices": [indices des passages utilisés, base 0]}
TEXT;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $respondModel,
        private readonly string $shortTaskModel,
        private readonly string $baseUrl,
    ) {}

    public function respond(string $systemPrompt, array $conversationHistory, array $knowledgeContext): AiReply
    {
        $system = trim($systemPrompt."\n\n".self::RESPOND_PROMPT."\n\nPassages disponibles :\n".$this->formatContext($knowledgeContext));

        $text = $this->complete($this->respondModel, $system, $conversationHistory, 1024);
        $parsed = json_decode(trim($text), true);

        if (! is_array($parsed) || ! is_string($parsed['answer'] ?? null)) {
            return new AiReply($text !== '' ? $text : 'Je ne suis pas certain de pouvoir répondre.', [], 0.0);
        }

        $indices = is_array($parsed['citation_indices'] ?? null) ? $parsed['citation_indices'] : [];
        $citations = [];
        foreach ($indices as $index) {
            if (is_int($index) && isset($knowledgeContext[$index])) {
                $citations[] = $knowledgeContext[$index];
            }
        }

        return new AiReply($parsed['answer'], $citations, max(0.0, min(1.0, (float) ($parsed['confidence'] ?? 0))));
    }

    public function summarize(array $conversationHistory): string
    {
        return trim($this->complete(
            $this->shortTaskModel,
            'Résume cette conversation de support client en deux phrases, en français, sans préambule.',
            $conversationHistory,
            256,
        ));
    }

    public function analyzeSentiment(array $conversationHistory): string
    {
        return trim(mb_strtolower($this->complete(
            $this->shortTaskModel,
            'Analyse le sentiment du visiteur dans cette conversation. Réponds uniquement par un mot : positif, neutre ou négatif.',
            $conversationHistory,
            16,
        )));
    }

    /** @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory */
    private function complete(string $model, string $system, array $conversationHistory, int $maxTokens): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException("ANTHROPIC_API_KEY est absente : l'agent IA ne peut pas appeler l'API.");
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders(['x-api-key' => $this->apiKey, 'anthropic-version' => '2023-06-01'])
            ->timeout(60)
            ->post('/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => $this->toApiMessages($conversationHistory),
            ])
            ->throw();

        return $this->extractText($response);
    }

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory
     * @return list<array{role: string, content: string}>
     */
    private function toApiMessages(array $conversationHistory): array
    {
        if ($conversationHistory === []) {
            return [['role' => 'user', 'content' => '(Aucun message.)']];
        }

        return array_map(fn (array $message) => ['role' => $message['role'], 'content' => $message['content']], $conversationHistory);
    }

    private function extractText(Response $response): string
    {
        $parts = $response->json('content', []);

        return implode('', array_map(
            fn (array $part) => (string) ($part['text'] ?? ''),
            is_array($parts) ? $parts : [],
        ));
    }

    /** @param  list<array{title: string, content: string, citation_url: string|null}>  $context */
    private function formatContext(array $context): string
    {
        if ($context === []) {
            return '(aucun passage disponible)';
        }

        $lines = [];
        foreach ($context as $index => $passage) {
            $lines[] = "[{$index}] {$passage['title']} : {$passage['content']}";
        }

        return implode("\n\n", $lines);
    }
}
