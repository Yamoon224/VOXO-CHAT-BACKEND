<?php

namespace App\Domains\Tickets\Services;

use App\Domains\Contacts\Contracts\ContactLinkContract;
use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Tickets\Contracts\TicketRepositoryContract;
use App\Domains\Tickets\Enums\TicketPriority;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Côté client, sans compte : ouvrir une demande, retrouver et suivre ses
 * tickets par lien signé — voir `TicketPortalToken`.
 */
final class TicketPortalService
{
    public function __construct(
        private readonly TicketRepositoryContract $tickets,
        private readonly ContactRepositoryContract $contactsRepository,
        private readonly ContactLinkContract $contacts,
        private readonly VisitorConversationContract $conversations,
        private readonly MessageRepositoryContract $messages,
        private readonly TransactionalMailerContract $mailer,
        private readonly TicketPortalTokenService $portalTokens,
        private readonly TransactionManagerContract $transactions,
    ) {}

    public function openPublicTicket(string $workspaceId, string $email, ?string $name, string $subject, string $message): Ticket
    {
        return $this->transactions->run(function () use ($workspaceId, $email, $name, $subject, $message) {
            $contactId = $this->contacts->findOrCreateIdByEmail($workspaceId, $email, $name);

            $conversation = $this->conversations->receiveChannelMessage(
                $workspaceId,
                'ticket',
                $email,
                $message,
                $name,
                $email,
                $contactId,
                $subject,
            );

            $ticket = $this->tickets->create([
                'workspace_id' => $workspaceId,
                'conversation_id' => $conversation->id,
                'contact_id' => $contactId,
                'priority' => TicketPriority::Normal,
            ]);

            $this->mailer->sendTicketPortalLink($email, $name ?? $email, $subject, $this->portalTokens->issue($workspaceId, (string) $contactId));

            return $ticket->setRelation('conversation', $conversation);
        });
    }

    /**
     * Réémet le lien de portail d'un contact connu, sans jamais révéler s'il
     * existe ou a des tickets — même garde que `PasswordResetService::requestLink()`.
     */
    public function resendLink(string $workspaceId, string $email): void
    {
        $contact = $this->contactsRepository->findByEmail($workspaceId, $email);

        if ($contact === null) {
            return;
        }

        $tickets = $this->tickets->forContact($workspaceId, $contact->id);

        if ($tickets->isEmpty()) {
            return;
        }

        $latest = $tickets->first();

        $this->mailer->sendTicketPortalLink(
            $email,
            $contact->name ?? $email,
            $latest?->conversation->subject ?? 'Vos demandes',
            $this->portalTokens->issue($workspaceId, $contact->id),
        );
    }

    /** @return Collection<int, Ticket> */
    public function listForContact(string $token): Collection
    {
        $claims = $this->portalTokens->verify($token);

        return $this->tickets->forContact($claims['workspaceId'], $claims['contactId']);
    }

    public function getForContact(string $token, string $ticketId): Ticket
    {
        $claims = $this->portalTokens->verify($token);

        return $this->tickets->findForContact($claims['workspaceId'], $claims['contactId'], $ticketId)
            ?? throw new ModelNotFoundException;
    }

    /** @return Collection<int, Message> */
    public function messagesFor(Ticket $ticket): Collection
    {
        return $this->messages->publicForConversation($ticket->conversation_id);
    }

    public function postMessage(string $token, string $ticketId, string $body): Ticket
    {
        $ticket = $this->getForContact($token, $ticketId);

        $this->conversations->receiveReplyOnKnownConversation($ticket->workspace_id, $ticket->conversation_id, $body);

        return $ticket;
    }
}
