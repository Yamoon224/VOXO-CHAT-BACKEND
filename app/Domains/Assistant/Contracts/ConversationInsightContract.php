<?php

namespace App\Domains\Assistant\Contracts;

use App\Models\Conversation;

/**
 * Lecture étroite exposée au domaine `Conversations` : résumé de conversation
 * et analyse de sentiment (section 2.2), sans rien connaître du fournisseur
 * d'IA derrière.
 */
interface ConversationInsightContract
{
    public function summarize(string $workspaceId, string $conversationId): Conversation;

    public function analyzeSentiment(string $workspaceId, string $conversationId): Conversation;
}
