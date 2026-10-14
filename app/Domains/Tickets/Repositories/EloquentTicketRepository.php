<?php

namespace App\Domains\Tickets\Repositories;

use App\Domains\Tickets\Contracts\TicketRepositoryContract;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentTicketRepository implements TicketRepositoryContract
{
    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Ticket::query()
            ->with(['conversation.assignedUser:id,name', 'contact:id,name,email'])
            ->where('workspace_id', $workspaceId)
            ->when($filters['priority'] ?? null, fn (Builder $query, mixed $priority) => $query->where('priority', $priority))
            ->when(
                array_key_exists('overdue', $filters) && (bool) $filters['overdue'],
                fn (Builder $query) => $query->whereNotNull('due_at')->where('due_at', '<', now())
                    ->whereRelation('conversation', 'status', '!=', 'resolved'),
            )
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Ticket
    {
        return Ticket::query()
            ->with(['conversation.assignedUser:id,name', 'contact:id,name,email'])
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);
    }

    public function findForContact(string $workspaceId, string $contactId, string $id): ?Ticket
    {
        return Ticket::query()
            ->with(['conversation', 'contact:id,name,email'])
            ->where('workspace_id', $workspaceId)
            ->where('contact_id', $contactId)
            ->find($id);
    }

    public function forContact(string $workspaceId, string $contactId): Collection
    {
        return Ticket::query()
            ->with('conversation')
            ->where('workspace_id', $workspaceId)
            ->where('contact_id', $contactId)
            ->latest('created_at')
            ->get();
    }

    public function findByConversationId(string $conversationId): ?Ticket
    {
        return Ticket::query()->where('conversation_id', $conversationId)->first();
    }

    public function create(array $attributes): Ticket
    {
        return Ticket::create($attributes);
    }

    public function update(Ticket $ticket, array $attributes): Ticket
    {
        $ticket->update($attributes);

        return $ticket;
    }
}
