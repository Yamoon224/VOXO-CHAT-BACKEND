<?php

namespace App\Domains\Channels\Contracts;

/**
 * Lecture étroite exposée au domaine `Conversations` : une réponse publique
 * vient d'être postée, à relayer vers le canal d'origine si celui-ci en a
 * besoin (`email`). `$channel` est la valeur brute de `ConversationChannel`
 * (primitif, jamais l'enum — ce domaine ne dépend pas de `Conversations`).
 * Sans effet pour un canal sans relais sortant (`widget`) ou sans adresse
 * connue.
 */
interface OutboundChannelDispatcherContract
{
    public function dispatchReply(string $workspaceId, string $channel, ?string $recipientEmail, string $body): void;
}
