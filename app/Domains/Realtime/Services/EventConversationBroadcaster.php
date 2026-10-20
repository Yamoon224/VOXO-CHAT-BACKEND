<?php

namespace App\Domains\Realtime\Services;

use App\Domains\Realtime\Contracts\ConversationBroadcasterContract;
use App\Domains\Realtime\Events\MessageBroadcastEvent;
use App\Models\Message;

/** Pilote par défaut : passe par le système d'évènements de Laravel (donc par le pilote de diffusion configuré, Reverb en production). */
final class EventConversationBroadcaster implements ConversationBroadcasterContract
{
    public function messageCreated(Message $message): void
    {
        event(new MessageBroadcastEvent($message));
    }
}
