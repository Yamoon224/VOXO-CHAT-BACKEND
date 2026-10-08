<?php

namespace Tests\Support\Fakes;

use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Models\Message;
use Illuminate\Support\Collection;

final class InMemoryMessageRepository implements MessageRepositoryContract
{
    /** @var array<string, Message> */
    private array $messages = [];

    public function forConversation(string $conversationId): Collection
    {
        return (new Collection($this->messages))
            ->filter(fn (Message $m) => $m->conversation_id === $conversationId)
            ->sortBy(fn (Message $m) => $m->created_at)
            ->values();
    }

    public function publicForConversation(string $conversationId): Collection
    {
        return $this->forConversation($conversationId)
            ->filter(fn (Message $m) => $m->visibility === MessageVisibility::Public)
            ->values();
    }

    public function create(array $attributes): Message
    {
        $message = ModelFactory::message($attributes);

        return $this->messages[$message->id] = $message;
    }

    public function recordAiReply(string $conversationId, string $workspaceId, string $body, array $citations): Message
    {
        return $this->create([
            'conversation_id' => $conversationId,
            'workspace_id' => $workspaceId,
            'sender_type' => 'ai',
            'body' => $body,
            'citations' => $citations,
        ]);
    }

    public function recordSystemNotice(string $conversationId, string $workspaceId, string $body): Message
    {
        return $this->create([
            'conversation_id' => $conversationId,
            'workspace_id' => $workspaceId,
            'sender_type' => 'system',
            'body' => $body,
        ]);
    }
}
