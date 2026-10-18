<?php

namespace App\Domains\Assistant\Contracts;

use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Assistant\DTOs\ToolDefinition;

/**
 * Génération par l'API Claude (section 4.6 du cahier des charges) : Claude
 * Sonnet 5.5 pour l'agent, Claude Haiku 4.5 pour les tâches courtes (résumé,
 * sentiment). Le modèle est un réglage de l'implémentation, pas de ce contrat.
 */
interface AiProviderContract
{
    /**
     * `$workspaceId` sert uniquement à retrouver les outils déclarés
     * (`ToolInvokerContract`) pendant la boucle d'appel — jamais à filtrer
     * l'historique ni le contexte, déjà bornés à l'espace par l'appelant.
     *
     * @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory
     * @param  list<array{title: string, content: string, citation_url: string|null}>  $knowledgeContext
     * @param  list<ToolDefinition>  $tools
     */
    public function respond(
        string $workspaceId,
        string $systemPrompt,
        array $conversationHistory,
        array $knowledgeContext,
        array $tools = [],
    ): AiReply;

    /** @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory */
    public function summarize(array $conversationHistory): string;

    /** @param  list<array{role: 'user'|'assistant', content: string}>  $conversationHistory */
    public function analyzeSentiment(array $conversationHistory): string;
}
