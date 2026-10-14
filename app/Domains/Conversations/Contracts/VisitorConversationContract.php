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

    /**
     * Même mécanique que `receiveVisitorMessage`, pour un canal autre que le
     * widget (`Channels`) : `$channel` est la valeur brute de
     * `ConversationChannel`, jamais l'enum lui-même — ce domaine ne doit pas
     * en dépendre au-delà de ce contrat. `$contactId`, déjà résolu par
     * l'appelant via `ContactLinkContract`, est rattaché à la conversation.
     */
    public function receiveChannelMessage(
        string $workspaceId,
        string $channel,
        string $visitorId,
        string $body,
        ?string $visitorName = null,
        ?string $visitorEmail = null,
        ?string $contactId = null,
        ?string $subject = null,
    ): Conversation;

    /**
     * Un visiteur répond sur une conversation déjà identifiée par l'appelant
     * (portail ticket, `Tickets`) : pas de recherche par identifiant visiteur
     * comme `receiveChannelMessage` — l'appelant tient déjà la conversation en
     * main. Rouvre la conversation si elle était résolue : répondre à une
     * demande close est un signe qu'elle ne l'est plus.
     */
    public function receiveReplyOnKnownConversation(string $workspaceId, string $conversationId, string $body): Conversation;

    public function rateByVisitor(string $workspaceId, string $conversationId, int $rating, ?string $comment): Conversation;
}
