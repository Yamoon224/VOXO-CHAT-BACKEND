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
use Illuminate\Support\Facades\DB;

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
            ->when($filters['channel'] ?? null, fn (Builder $query, mixed $channel) => $query->where('channel', $channel))
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

    public function averageFirstResponseSeconds(string $workspaceId, Carbon $from, Carbon $to): ?float
    {
        $conversations = $this->scopedByPeriod($workspaceId, $from, $to)->get(['id', 'created_at']);

        if ($conversations->isEmpty()) {
            return null;
        }

        $firstResponseAt = Message::query()
            ->whereIn('conversation_id', $conversations->pluck('id'))
            ->where('sender_type', '!=', 'visitor')
            ->select('conversation_id', DB::raw('MIN(created_at) as first_response_at'))
            ->groupBy('conversation_id')
            ->pluck('first_response_at', 'conversation_id');

        $delays = $conversations
            ->filter(fn (Conversation $conversation) => isset($firstResponseAt[$conversation->id]))
            ->map(fn (Conversation $conversation) => Carbon::parse($firstResponseAt[$conversation->id])->diffInSeconds($conversation->created_at, true));

        return $delays->isEmpty() ? null : (float) $delays->avg();
    }

    public function averageResolutionSeconds(string $workspaceId, Carbon $from, Carbon $to): ?float
    {
        $delays = $this->scopedByPeriod($workspaceId, $from, $to)
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at'])
            ->map(fn (Conversation $conversation) => $conversation->resolved_at->diffInSeconds($conversation->created_at, true));

        return $delays->isEmpty() ? null : (float) $delays->avg();
    }

    public function countMessagesByAgent(string $workspaceId, Carbon $from, Carbon $to): array
    {
        return Message::query()
            ->where('workspace_id', $workspaceId)
            ->where('sender_type', 'agent')
            ->whereNotNull('sender_user_id')
            ->whereBetween('created_at', [$from, $to])
            ->select('sender_user_id', DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy('sender_user_id')
            ->get()
            ->map(fn (Message $row) => ['user_id' => $row->sender_user_id, 'count' => (int) $row->getAttribute('aggregate_count')])
            ->all();
    }

    public function countConversationsByChannel(string $workspaceId, Carbon $from, Carbon $to): array
    {
        return $this->scopedByPeriod($workspaceId, $from, $to)
            ->select('channel', DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy('channel')
            ->get()
            ->map(fn (Conversation $row) => ['channel' => $row->channel->value, 'count' => (int) $row->getAttribute('aggregate_count')])
            ->all();
    }

    public function topCitedKnowledgeDocuments(string $workspaceId, Carbon $from, Carbon $to, int $limit = 5): array
    {
        return Message::query()
            ->where('workspace_id', $workspaceId)
            ->where('sender_type', 'ai')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('citations')
            ->pluck('citations')
            ->flatMap(fn (array $citations) => array_column($citations, 'title'))
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->map(fn (int $count, string $title) => ['title' => $title, 'count' => $count])
            ->values()
            ->all();
    }

    public function unansweredKnowledgeQuestions(string $workspaceId, Carbon $from, Carbon $to, int $limit = 10): array
    {
        $unanswered = Message::query()
            ->where('workspace_id', $workspaceId)
            ->where('sender_type', 'ai')
            ->whereBetween('created_at', [$from, $to])
            ->where(fn (Builder $query) => $query->whereNull('citations')->orWhereJsonLength('citations', 0))
            ->orderByDesc('created_at');

        $count = $unanswered->count();

        $recent = $unanswered->limit($limit)->get(['conversation_id', 'created_at'])
            ->map(function (Message $aiMessage) {
                $question = Message::query()
                    ->where('conversation_id', $aiMessage->conversation_id)
                    ->where('sender_type', 'visitor')
                    ->where('created_at', '<=', $aiMessage->created_at)
                    ->latest('created_at')
                    ->value('body');

                return $question === null ? null : ['question' => $question, 'created_at' => $aiMessage->created_at];
            })
            ->filter(fn (?array $row) => $row !== null)
            ->values()
            ->all();

        return ['count' => $count, 'recent' => $recent];
    }

    /** @return Builder<Conversation> */
    private function scopedByPeriod(string $workspaceId, Carbon $from, Carbon $to): Builder
    {
        return Conversation::query()->where('workspace_id', $workspaceId)->whereBetween('created_at', [$from, $to]);
    }
}
