<?php

namespace App\Domains\Channels\Contracts;

/**
 * Lecture étroite exposée au domaine `Conversations` : une réponse publique
 * vient d'être postée, à relayer vers le canal d'origine si celui-ci en a
 * besoin (`email`, `whatsapp`, `messenger`, `instagram`, `telegram`, `sms`).
 * `$channel` est la valeur brute de `ConversationChannel` (primitif, jamais
 * l'enum — ce domaine ne dépend pas de `Conversations`). `$recipientAddress`
 * est `Conversation::visitor_id` — l'adresse qui identifie le destinataire
 * dans son canal (e-mail, numéro de téléphone, PSID/IGSID, chat id Telegram
 * selon le cas), déjà résolue par l'appelant. Sans effet pour un canal sans
 * relais sortant (`widget`) ou sans adresse connue.
 */
interface OutboundChannelDispatcherContract
{
    public function dispatchReply(string $workspaceId, string $channel, ?string $recipientAddress, string $body): void;
}
