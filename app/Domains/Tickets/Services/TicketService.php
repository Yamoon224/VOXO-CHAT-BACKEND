<?php

namespace App\Domains\Tickets\Services;

use App\Domains\Contacts\Contracts\ContactLinkContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Tickets\Contracts\TicketRepositoryContract;
use App\Domains\Tickets\Enums\TicketPriority;
use App\Domains\Tickets\Exceptions\TicketAlreadyExistsException;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/** Côté agent : convertir une conversation en ticket, et en suivre priorité et échéance. */
final class TicketService
{
    public function __construct(
        private readonly TicketRepositoryContract $tickets,
        private readonly ConversationRepositoryContract $conversations,
        private readonly ContactLinkContract $contacts,
        private readonly TransactionalMailerContract $mailer,
        private readonly TicketPortalTokenService $portalTokens,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function list(WorkspaceScope $scope, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->tickets->paginate($scope->workspaceId, $filters, $perPage);
    }

    public function get(WorkspaceScope $scope, string $ticketId): Ticket
    {
        return $this->tickets->findInWorkspaceOrFail($scope->workspaceId, $ticketId);
    }

    /** @throws TicketAlreadyExistsException */
    public function openFromConversation(
        WorkspaceScope $scope,
        string $conversationId,
        TicketPriority $priority,
        ?Carbon $dueAt,
    ): Ticket {
        $conversation = $this->conversations->findInWorkspaceOrFail($scope->workspaceId, $conversationId);

        if ($this->tickets->findByConversationId($conversation->id) !== null) {
            throw TicketAlreadyExistsException::make();
        }

        // Un visiteur du widget peut n'avoir jamais été rattaché à un contact
        // (lot 2, antérieur à `Contacts`) : un ticket a besoin d'un contact
        // pour que le portail ait un destinataire, donc on le résout ici si
        // une adresse existe déjà sur la conversation.
        $contactId = $conversation->contact_id ?? $this->contacts->findOrCreateIdByEmail(
            $scope->workspaceId,
            $conversation->visitor_email,
            $conversation->visitor_name,
        );

        if ($contactId !== null && $contactId !== $conversation->contact_id) {
            $conversation = $this->conversations->update($conversation, ['contact_id' => $contactId]);
        }

        $ticket = $this->tickets->create([
            'workspace_id' => $scope->workspaceId,
            'conversation_id' => $conversation->id,
            'contact_id' => $contactId,
            'priority' => $priority,
            'due_at' => $dueAt,
        ]);

        if ($conversation->visitor_email !== null && $contactId !== null) {
            $this->mailer->sendTicketPortalLink(
                $conversation->visitor_email,
                $conversation->visitor_name ?? $conversation->visitor_email,
                $conversation->subject ?? 'Votre demande',
                $this->portalTokens->issue($scope->workspaceId, $contactId),
            );
        }

        // Recharge pour obtenir `contact` et `conversation.assignedUser`, que
        // `create()` ne charge pas — `TicketResource` en a besoin.
        return $this->tickets->findInWorkspaceOrFail($scope->workspaceId, $ticket->id);
    }

    /** @param  array{priority?: TicketPriority, due_at?: Carbon|null}  $attributes */
    public function update(WorkspaceScope $scope, string $ticketId, array $attributes): Ticket
    {
        return $this->tickets->update($this->get($scope, $ticketId), $attributes);
    }
}
