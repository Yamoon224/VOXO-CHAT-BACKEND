<?php

namespace App\Domains\Conversations\Repositories;

use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Domains\Realtime\Contracts\ConversationBroadcasterContract;
use App\Models\Message;
use Illuminate\Support\Collection;

/**
 * Diffuse chaque message créé vers ses canaux temps réel (section 4.1) ici,
 * au plus près de la persistance : un message naît par 5 chemins différents
 * (`MessageService`, `ConversationService`, `Assistant`, `Automations`,
 * `Campaigns`) — centraliser la diffusion dans le seul point par lequel ils
 * passent tous évite qu'un futur appelant l'oublie.
 */
final class EloquentMessageRepository implements MessageRepositoryContract
{
    public function __construct(private readonly ConversationBroadcasterContract $broadcaster) {}

    /** @return Collection<int, Message> */
    public function forConversation(string $conversationId): Collection
    {
        return Message::query()
            ->where('conversation_id', $conversationId)
            ->with('senderUser:id,name')
            ->oldest('created_at')
            ->get();
    }

    /** @return Collection<int, Message> */
    public function publicForConversation(string $conversationId): Collection
    {
        return Message::query()
            ->where('conversation_id', $conversationId)
            ->where('visibility', MessageVisibility::Public)
            ->oldest('created_at')
            ->get();
    }

    public function create(array $attributes): Message
    {
        $message = Message::create($attributes);
        $this->broadcaster->messageCreated($message);

        return $message;
    }

    public function recordAiReply(string $conversationId, string $workspaceId, string $body, array $citations): Message
    {
        $message = Message::create([
            'conversation_id' => $conversationId,
            'workspace_id' => $workspaceId,
            'sender_type' => MessageSenderType::Ai,
            // Explicite, pas seulement le défaut de colonne : voir la note
            // sur `create()` plus haut.
            'visibility' => MessageVisibility::Public,
            'body' => $body,
            'citations' => $citations,
        ]);
        $this->broadcaster->messageCreated($message);

        return $message;
    }

    public function recordSystemNotice(string $conversationId, string $workspaceId, string $body): Message
    {
        $message = Message::create([
            'conversation_id' => $conversationId,
            'workspace_id' => $workspaceId,
            'sender_type' => MessageSenderType::System,
            'visibility' => MessageVisibility::Public,
            'body' => $body,
        ]);
        $this->broadcaster->messageCreated($message);

        return $message;
    }
}
