<?php

namespace App\Domains\Assistant\Contracts;

use App\Domains\Assistant\DTOs\AiReply;

/**
 * Génération par l'API Claude (section 4.6 du cahier des charges) : Claude
 * Sonnet 5.5 pour l'agent, Claude Haiku 4.5 pour les tâches courtes (résumé,
 * sentiment). Le modèle est un réglage de l'implémentation, pas de ce contrat.
 */
interface AiProviderContract
{
    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory
     * @param  list<array{title: string, content: string, citation_url: string|null}>  $knowledgeContext
     */
    public function respond(string $systemPrompt, array $conversationHistory, array $knowledgeContext): AiReply;

    /** @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory */
    public function summarize(array $conversationHistory): string;

    /** @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory */
    public function analyzeSentiment(array $conversationHistory): string;
}
