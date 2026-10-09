<?php

namespace App\Domains\Conversations\Repositories;

use App\Domains\Conversations\Contracts\ConversationReaderContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Domains\Shared\Support\Sort;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class EloquentConversationRepository implements ConversationReaderContract, ConversationRepositoryContract
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

    public function escalateToHuman(Conversation $conversation): Conversation
    {
        $conversation->update([
            'needs_human' => true,
            'status' => ConversationStatus::Pending,
            'last_message_at' => now(),
        ]);

        return $conversation;
    }

    public function countConversations(string $workspaceId, Carbon $from, Carbon $to): int
    {
        return $this->scopedByPeriod($workspaceId, $from, $to)->count();
    }

    public function countMessages(string $workspaceId, Carbon $from, Carbon $to): int
    {
        return Message::query()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    public function averageRating(string $workspaceId, Carbon $from, Carbon $to): ?float
    {
        $average = $this->scopedByPeriod($workspaceId, $from, $to)->whereNotNull('rating')->avg('rating');

        return $average === null ? null : (float) $average;
    }

    public function countByResolutionOutcome(string $workspaceId, Carbon $from, Carbon $to): array
    {
        $aiResolved = $this->scopedByPeriod($workspaceId, $from, $to)
            ->where('needs_human', false)
            ->whereHas('messages', fn (Builder $query) => $query->where('sender_type', 'ai'))
            ->count();

        $escalated = $this->scopedByPeriod($workspaceId, $from, $to)
            ->where(fn (Builder $query) => $query
                ->where('needs_human', true)
                ->orWhereHas('messages', fn (Builder $message) => $message->where('sender_type', 'system')))
            ->count();

        $unanswered = $this->scopedByPeriod($workspaceId, $from, $to)
            ->whereDoesntHave('messages', fn (Builder $query) => $query->whereIn('sender_type', ['ai', 'agent']))
            ->count();

        return ['ai_resolved' => $aiResolved, 'escalated' => $escalated, 'unanswered' => $unanswered];
    }

    /** @return Builder<Conversation> */
    private function scopedByPeriod(string $workspaceId, Carbon $from, Carbon $to): Builder
    {
        return Conversation::query()->where('workspace_id', $workspaceId)->whereBetween('created_at', [$from, $to]);
    }
}
