<?php

namespace App\Domains\Assistant\Contracts;

use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Assistant\Exceptions\AiCreditQuotaExceededException;
use App\Models\Conversation;

/**
 * Lecture étroite exposée au domaine `Conversations` : résumé de conversation,
 * analyse de sentiment, et copilote (section 2.2), sans rien connaître du
 * fournisseur d'IA derrière.
 */
interface ConversationInsightContract
{
    public function summarize(string $workspaceId, string $conversationId): Conversation;

    public function analyzeSentiment(string $workspaceId, string $conversationId): Conversation;

    /**
     * Copilote interne (section 2.2) : propose une réponse à l'agent, jamais
     * envoyée automatiquement — contrairement à `AssistantResponderContract`,
     * qui répond au visiteur. L'agent reste décisionnaire de l'envoi.
     *
     * @throws AiCreditQuotaExceededException
     */
    public function suggestReply(string $workspaceId, string $conversationId): AiReply;
}
