<?php

namespace App\Domains\Assistant\Providers;

use App\Domains\Assistant\Contracts\AiProviderContract;
use App\Domains\Assistant\Contracts\ToolInvokerContract;
use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Assistant\DTOs\ToolDefinition;
use App\Domains\Assistant\DTOs\ToolParameter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Agent IA par l'API Claude (section 4.6) : Sonnet 5.5 pour répondre,
 * Haiku 4.5 pour les tâches courtes.
 *
 * Claude ne renvoie pas nativement de score de confiance : la réponse finale
 * est demandée en JSON strict (`answer`, `confidence`, `citation_indices`)
 * pour en obtenir un exploitable. Une réponse mal formée retombe sur une
 * confiance nulle — en échec, on escalade plutôt que d'inventer une réponse.
 *
 * Outils déclarés (section 2.2, P3) : quand Claude répond `stop_reason =
 * tool_use`, l'outil est exécuté via `ToolInvokerContract` et son résultat
 * renvoyé comme `tool_result`, en boucle jusqu'à une réponse texte finale ou
 * `MAX_TOOL_ITERATIONS` — jamais indéfiniment, un outil mal déclaré ne doit
 * pas transformer une réponse en boucle infinie d'appels réseau.
 */
final class ClaudeAiProvider implements AiProviderContract
{
    private const RESPOND_PROMPT = <<<'TEXT'
Réponds uniquement à partir des passages fournis ci-dessous, avec leurs
numéros entre crochets, et des outils mis à ta disposition si besoin. Si ni
les passages ni les outils ne permettent de répondre avec certitude, dis-le
et baisse ta confiance en conséquence plutôt que d'inventer.

Une fois prêt à répondre (après avoir utilisé les outils nécessaires le cas
échéant), réponds strictement en JSON, sans aucun texte autour, au format :
{"answer": "...", "confidence": nombre entre 0 et 1, "citation_indices": [indices des passages utilisés, base 0]}
TEXT;

    private const MAX_TOOL_ITERATIONS = 3;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $respondModel,
        private readonly string $shortTaskModel,
        private readonly string $baseUrl,
        private readonly ToolInvokerContract $tools,
    ) {}

    public function respond(string $workspaceId, string $systemPrompt, array $conversationHistory, array $knowledgeContext, array $tools = []): AiReply
    {
        $system = trim($systemPrompt."\n\n".self::RESPOND_PROMPT."\n\nPassages disponibles :\n".$this->formatContext($knowledgeContext));
        $messages = $this->toApiMessages($conversationHistory);
        $toolsPayload = $this->toApiTools($tools);
        $toolCallsUsed = [];

        for ($iteration = 0; $iteration < self::MAX_TOOL_ITERATIONS; $iteration++) {
            $response = $this->send($this->respondModel, $system, $messages, 1024, $toolsPayload);
            $content = $this->rawContent($response);
            $toolUse = $this->firstToolUseBlock($content);

            if ($response->json('stop_reason') !== 'tool_use' || $toolUse === null) {
                return $this->parseFinalAnswer($this->textFrom($content), $knowledgeContext, $toolCallsUsed);
            }

            $toolCallsUsed[] = $toolUse['name'];
            $arguments = is_array($toolUse['input'] ?? null) ? $toolUse['input'] : [];
            $result = $this->tools->invoke($workspaceId, $toolUse['name'], $arguments);

            $messages[] = ['role' => 'assistant', 'content' => $content];
            $messages[] = ['role' => 'user', 'content' => [['type' => 'tool_result', 'tool_use_id' => $toolUse['id'], 'content' => $result]]];
        }

        // Limite atteinte : une dernière tentative sans outils force une
        // réponse texte plutôt que de boucler indéfiniment.
        $content = $this->rawContent($this->send($this->respondModel, $system, $messages, 1024, []));

        return $this->parseFinalAnswer($this->textFrom($content), $knowledgeContext, $toolCallsUsed);
    }

    public function summarize(array $conversationHistory): string
    {
        return trim($this->textFrom($this->rawContent($this->send(
            $this->shortTaskModel,
            'Résume cette conversation de support client en deux phrases, en français, sans préambule.',
            $this->toApiMessages($conversationHistory),
            256,
            [],
        ))));
    }

    public function analyzeSentiment(array $conversationHistory): string
    {
        return trim(mb_strtolower($this->textFrom($this->rawContent($this->send(
            $this->shortTaskModel,
            'Analyse le sentiment du visiteur dans cette conversation. Réponds uniquement par un mot : positif, neutre ou négatif.',
            $this->toApiMessages($conversationHistory),
            16,
            [],
        )))));
    }

    /**
     * @param  list<array{role: string, content: mixed}>  $messages
     * @param  list<array{name: string, description: string, input_schema: array<string, mixed>}>  $toolsPayload
     */
    private function send(string $model, string $system, array $messages, int $maxTokens, array $toolsPayload): Response
    {
        if ($this->apiKey === '') {
            throw new RuntimeException("ANTHROPIC_API_KEY est absente : l'agent IA ne peut pas appeler l'API.");
        }

        $payload = ['model' => $model, 'max_tokens' => $maxTokens, 'system' => $system, 'messages' => $messages];

        if ($toolsPayload !== []) {
            $payload['tools'] = $toolsPayload;
        }

        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['x-api-key' => $this->apiKey, 'anthropic-version' => '2023-06-01'])
            ->timeout(60)
            ->post('/messages', $payload)
            ->throw();
    }

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory
     * @return list<array{role: string, content: mixed}>
     */
    private function toApiMessages(array $conversationHistory): array
    {
        if ($conversationHistory === []) {
            return [['role' => 'user', 'content' => '(Aucun message.)']];
        }

        return array_map(fn (array $message) => ['role' => $message['role'], 'content' => $message['content']], $conversationHistory);
    }

    /**
     * @param  list<ToolDefinition>  $tools
     * @return list<array{name: string, description: string, input_schema: array<string, mixed>}>
     */
    private function toApiTools(array $tools): array
    {
        return array_map(fn (ToolDefinition $tool) => [
            'name' => $tool->name,
            'description' => $tool->description,
            'input_schema' => [
                'type' => 'object',
                'properties' => array_reduce(
                    $tool->parameters,
                    fn (array $properties, ToolParameter $parameter) => $properties + [
                        $parameter->name => ['type' => $parameter->type, 'description' => $parameter->description],
                    ],
                    [],
                ),
                'required' => array_values(array_map(
                    fn (ToolParameter $parameter) => $parameter->name,
                    array_filter($tool->parameters, fn (ToolParameter $parameter) => $parameter->required),
                )),
            ],
        ], $tools);
    }

    /** @return list<array<string, mixed>> */
    private function rawContent(Response $response): array
    {
        $content = $response->json('content', []);

        return is_array($content) ? $content : [];
    }

    /**
     * @param  list<array<string, mixed>>  $content
     * @return array{id: string, name: string, input: mixed}|null
     */
    private function firstToolUseBlock(array $content): ?array
    {
        foreach ($content as $block) {
            if (($block['type'] ?? null) === 'tool_use' && is_string($block['name'] ?? null) && is_string($block['id'] ?? null)) {
                return ['id' => $block['id'], 'name' => $block['name'], 'input' => $block['input'] ?? []];
            }
        }

        return null;
    }

    /** @param  list<array<string, mixed>>  $content */
    private function textFrom(array $content): string
    {
        return implode('', array_map(fn (array $part) => (string) ($part['text'] ?? ''), $content));
    }

    /**
     * @param  list<array{title: string, content: string, citation_url: string|null}>  $knowledgeContext
     * @param  list<string>  $toolCallsUsed
     */
    private function parseFinalAnswer(string $text, array $knowledgeContext, array $toolCallsUsed): AiReply
    {
        $parsed = json_decode(trim($text), true);

        if (! is_array($parsed) || ! is_string($parsed['answer'] ?? null)) {
            return new AiReply($text !== '' ? $text : 'Je ne suis pas certain de pouvoir répondre.', [], 0.0, $toolCallsUsed);
        }

        $indices = is_array($parsed['citation_indices'] ?? null) ? $parsed['citation_indices'] : [];
        $citations = [];
        foreach ($indices as $index) {
            if (is_int($index) && isset($knowledgeContext[$index])) {
                $citations[] = $knowledgeContext[$index];
            }
        }

        return new AiReply($parsed['answer'], $citations, max(0.0, min(1.0, (float) ($parsed['confidence'] ?? 0))), $toolCallsUsed);
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
