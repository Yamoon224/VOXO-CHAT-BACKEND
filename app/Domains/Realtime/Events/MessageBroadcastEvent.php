<?php

namespace App\Domains\Realtime\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Diffusé juste après la persistance d'un message (lot 2, section 4.1).
 *
 * Deux canaux par conversation : `conversation.{id}` (réservé aux agents —
 * tous les messages, notes internes comprises, voir `routes/channels.php`)
 * et `conversation.{id}.public` (ouvert au visiteur propriétaire ET aux
 * agents — jamais l'inverse, sinon une note interne atteindrait le
 * navigateur du visiteur). Une note interne n'est donc jamais ajoutée au
 * second canal.
 *
 * `ShouldBroadcastNow` plutôt que `ShouldBroadcast` : le temps réel perd son
 * sens si la diffusion attend un tour de file d'attente.
 */
final class MessageBroadcastEvent implements ShouldBroadcastNow
{
    public function __construct(private readonly Message $message) {}

    /** @return list<Channel> */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("conversation.{$this->message->conversation_id}")];

        if ($this->message->isPublic()) {
            $channels[] = new PresenceChannel("conversation.{$this->message->conversation_id}.public");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_type' => $this->message->sender_type->value,
            'sender_user' => $this->message->senderUser === null
                ? null
                : ['id' => $this->message->senderUser->id, 'name' => $this->message->senderUser->name],
            'visibility' => $this->message->visibility->value,
            'body' => $this->message->body,
            'citations' => $this->message->citations ?? [],
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
