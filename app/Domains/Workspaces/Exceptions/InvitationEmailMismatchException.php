<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Le compte connecté n'est pas celui que l'invitation désigne.
 */
final class InvitationEmailMismatchException extends DomainException
{
    public static function make(): self
    {
        return new self('Cette invitation est destinée à une autre adresse e-mail.', 'invitation_email_mismatch', 403);
    }
}
