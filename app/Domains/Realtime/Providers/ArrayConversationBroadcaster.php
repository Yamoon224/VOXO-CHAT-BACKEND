<?php

namespace App\Domains\Realtime\Providers;

use App\Domains\Realtime\Contracts\ConversationBroadcasterContract;
use App\Models\Message;

/** Conserve les messages « diffusés » en mémoire : pilote de la suite de tests, aucune connexion WebSocket n'est jamais ouverte. */
final class ArrayConversationBroadcaster implements ConversationBroadcasterContract
{
    /** @var list<Message> */
    private array $broadcasted = [];

    public function messageCreated(Message $message): void
    {
        $this->broadcasted[] = $message;
    }

    /** @return list<Message> */
    public function broadcasted(): array
    {
        return $this->broadcasted;
    }
}
