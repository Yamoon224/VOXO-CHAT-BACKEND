<?php

namespace App\Domains\Realtime\Contracts;

use App\Models\Message;

/**
 * Relaie un message créé vers les canaux temps réel de sa conversation
 * (lot 2, section 4.1 : Laravel Reverb pour le widget et la boîte de
 * réception) — appelé juste après la persistance, jamais à sa place.
 */
interface ConversationBroadcasterContract
{
    public function messageCreated(Message $message): void;
}
