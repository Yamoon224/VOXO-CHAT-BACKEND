<?php

namespace App\Domains\Automations\Contracts;

use App\Models\Conversation;

/**
 * Lecture étroite exposée au domaine `Conversations` : un message vient
 * d'être reçu, à évaluer contre les scénarios publiés (ou à faire avancer une
 * exécution déjà en cours) — section 2.8.
 */
interface AutomationResponderContract
{
    /** @return bool `true` si un scénario a pris en charge ce message — l'appelant ne doit alors pas aussi faire répondre l'agent IA. */
    public function applyTo(Conversation $conversation, string $messageBody, bool $isNewConversation): bool;
}
