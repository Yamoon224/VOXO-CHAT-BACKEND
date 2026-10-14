<?php

namespace App\Domains\Tickets\Contracts;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TicketRepositoryContract
{
    /**
     * @param  array<string, mixed>  $filters  `priority`, `overdue`
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Ticket;

    /** `null` si ce contact n'a pas accès à ce ticket, ou s'il n'existe pas : le portail ne distingue pas les deux. */
    public function findForContact(string $workspaceId, string $contactId, string $id): ?Ticket;

    /** @return Collection<int, Ticket> Les tickets de ce contact, du plus récent au plus ancien. */
    public function forContact(string $workspaceId, string $contactId): Collection;

    public function findByConversationId(string $conversationId): ?Ticket;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Ticket;

    /** @param  array<string, mixed>  $attributes */
    public function update(Ticket $ticket, array $attributes): Ticket;
}
