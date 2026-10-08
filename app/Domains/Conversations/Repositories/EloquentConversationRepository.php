<?php

namespace App\Domains\Conversations\Repositories;

use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Shared\Support\Sort;
use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentConversationRepository implements ConversationRepositoryContract
{
    /** @var array<string, string|array{0: string, 1: string}> */
    private const SORTABLE = ['last_message_at' => 'last_message_at'];

    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Conversation::query()
            ->with('assignedUser:id,name')
            ->where('workspace_id', $workspaceId)
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $status) => $query->where('status', $status))
            ->when($filters['assigned_user_id'] ?? null, fn (Builder $query, mixed $userId) => $query->where('assigned_user_id', $userId))
            ->when(array_key_exists('needs_human', $filters) && $filters['needs_human'] !== null, fn (Builder $query) => $query->where('needs_human', (bool) $filters['needs_human']))
            ->tap(fn (Builder $query) => Sort::apply($query, $filters, self::SORTABLE, 'last_message_at', 'desc'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Conversation
    {
        return Conversation::query()->with('assignedUser:id,name')->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function findOrFailById(string $id): Conversation
    {
        return Conversation::query()->findOrFail($id);
    }

    public function findOpenForVisitor(string $workspaceId, string $visitorId): ?Conversation
    {
        return Conversation::query()
            ->where('workspace_id', $workspaceId)
            ->where('visitor_id', $visitorId)
            ->where('status', '!=', 'resolved')
            ->latest('last_message_at')
            ->first();
    }

    public function findLatestForVisitor(string $workspaceId, string $visitorId): ?Conversation
    {
        return Conversation::query()
            ->where('workspace_id', $workspaceId)
            ->where('visitor_id', $visitorId)
            ->latest('last_message_at')
            ->first();
    }

    public function create(array $attributes): Conversation
    {
        return Conversation::create($attributes);
    }

    public function update(Conversation $conversation, array $attributes): Conversation
    {
        $conversation->update($attributes);

        return $conversation;
    }
}
