<?php

namespace App\Domains\Tickets\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Une conversation n'a qu'un seul ticket : la migration le garantit déjà, cette exception rend l'erreur lisible avant d'y buter. */
final class TicketAlreadyExistsException extends DomainException
{
    public static function make(): self
    {
        return new self('Cette conversation a déjà un ticket.', 'ticket_already_exists', 422);
    }
}
