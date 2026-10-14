<?php

namespace Tests\Support\Fakes;

use App\Domains\Tickets\Contracts\TicketRepositoryContract;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InMemoryTicketRepository implements TicketRepositoryContract
{
    /** @var array<string, Ticket> */
    private array $tickets = [];

    public function __construct(Ticket ...$tickets)
    {
        foreach ($tickets as $ticket) {
            $this->tickets[$ticket->id] = $ticket;
        }
    }

    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        $items = array_values(array_filter(
            $this->tickets,
            fn (Ticket $ticket) => $ticket->workspace_id === $workspaceId
                && (! isset($filters['priority']) || $ticket->priority->value === $filters['priority']),
        ));

        return new Paginator($items, count($items), $perPage, 1);
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Ticket
    {
        $ticket = $this->tickets[$id] ?? null;

        if ($ticket === null || $ticket->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $ticket;
    }

    public function findForContact(string $workspaceId, string $contactId, string $id): ?Ticket
    {
        $ticket = $this->tickets[$id] ?? null;

        if ($ticket === null || $ticket->workspace_id !== $workspaceId || $ticket->contact_id !== $contactId) {
            return null;
        }

        return $ticket;
    }

    public function forContact(string $workspaceId, string $contactId): Collection
    {
        return (new Collection($this->tickets))
            ->filter(fn (Ticket $ticket) => $ticket->workspace_id === $workspaceId && $ticket->contact_id === $contactId)
            ->sortByDesc(fn (Ticket $ticket) => $ticket->created_at)
            ->values();
    }

    public function findByConversationId(string $conversationId): ?Ticket
    {
        foreach ($this->tickets as $ticket) {
            if ($ticket->conversation_id === $conversationId) {
                return $ticket;
            }
        }

        return null;
    }

    public function create(array $attributes): Ticket
    {
        $ticket = ModelFactory::ticket($attributes);

        return $this->tickets[$ticket->id] = $ticket;
    }

    public function update(Ticket $ticket, array $attributes): Ticket
    {
        $ticket->setRawAttributes($attributes + $ticket->getAttributes(), true);

        return $ticket;
    }
}
