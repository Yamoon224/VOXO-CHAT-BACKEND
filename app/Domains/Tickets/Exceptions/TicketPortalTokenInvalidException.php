<?php

namespace App\Domains\Tickets\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Jeton de portail absent, falsifié, expiré, ou d'un autre espace de travail. */
final class TicketPortalTokenInvalidException extends DomainException
{
    public static function make(): self
    {
        return new self('Lien de portail invalide ou expiré.', 'ticket_portal_token_invalid', 401);
    }
}
