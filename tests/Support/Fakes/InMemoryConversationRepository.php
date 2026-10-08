<?php

namespace Tests\Support\Fakes;

use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InMemoryConversationRepository implements ConversationRepositoryContract
{
    /** @var array<string, Conversation> */
    private array $conversations = [];

    public function __construct(Conversation ...$conversations)
    {
        foreach ($conversations as $conversation) {
            $this->conversations[$conversation->id] = $conversation;
        }
    }

    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        $items = array_values(array_filter($this->conversations, fn (Conversation $c) => $c->workspace_id === $workspaceId));

        return new Paginator($items, count($items), $perPage, 1);
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Conversation
    {
        $conversation = $this->conversations[$id] ?? null;

        if ($conversation === null || $conversation->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $conversation;
    }

    public function findOrFailById(string $id): Conversation
    {
        return $this->conversations[$id] ?? throw new ModelNotFoundException;
    }

    public function findOpenForVisitor(string $workspaceId, string $visitorId): ?Conversation
    {
        foreach ($this->conversations as $conversation) {
            if ($conversation->workspace_id === $workspaceId
                && $conversation->visitor_id === $visitorId
                && $conversation->status !== ConversationStatus::Resolved) {
                return $conversation;
            }
        }

        return null;
    }

    public function findLatestForVisitor(string $workspaceId, string $visitorId): ?Conversation
    {
        return (new Collection($this->conversations))
            ->filter(fn (Conversation $c) => $c->workspace_id === $workspaceId && $c->visitor_id === $visitorId)
            ->sortByDesc(fn (Conversation $c) => $c->last_message_at)
            ->first();
    }

    public function create(array $attributes): Conversation
    {
        $conversation = ModelFactory::conversation($attributes);

        return $this->conversations[$conversation->id] = $conversation;
    }

    public function update(Conversation $conversation, array $attributes): Conversation
    {
        $conversation->setRawAttributes($attributes + $conversation->getAttributes(), true);

        return $conversation;
    }

    public function escalateToHuman(Conversation $conversation): Conversation
    {
        return $this->update($conversation, [
            'needs_human' => true,
            'status' => ConversationStatus::Pending,
            'last_message_at' => now(),
        ]);
    }
}
