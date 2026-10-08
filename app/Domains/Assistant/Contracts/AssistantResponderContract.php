<?php

namespace App\Domains\Assistant\Contracts;

/**
 * Lecture étroite exposée au domaine `Conversations` : il déclenche une
 * réponse de l'agent IA après un message de visiteur, sans rien connaître de
 * la recherche sémantique, du prompt ou du fournisseur derrière.
 */
interface AssistantResponderContract
{
    public function respondToConversation(string $conversationId): void;
}
