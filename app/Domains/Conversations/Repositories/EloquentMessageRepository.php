<?php

namespace App\Domains\Conversations\Repositories;

use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Models\Message;
use Illuminate\Support\Collection;

final class EloquentMessageRepository implements MessageRepositoryContract
{
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
        return Message::create($attributes);
    }
}
