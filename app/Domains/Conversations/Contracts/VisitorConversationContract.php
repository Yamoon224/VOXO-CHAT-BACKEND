<?php

namespace App\Domains\Conversations\Contracts;

use App\Models\Conversation;

/**
 * Lecture étroite exposée au domaine `Widget` : un visiteur écrit à l'espace
 * de travail et note sa conversation, sans rien connaître de l'affectation,
 * des statuts ni des notes internes de l'équipe.
 */
interface VisitorConversationContract
{
    public function receiveVisitorMessage(
        string $workspaceId,
        string $visitorId,
        string $body,
        ?string $visitorName = null,
        ?string $visitorEmail = null,
    ): Conversation;

    public function rateByVisitor(string $workspaceId, string $conversationId, int $rating, ?string $comment): Conversation;
}
