<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * L'invitation a existé mais n'est plus utilisable.
 */
final class InvitationExpiredException extends DomainException
{
    public static function make(): self
    {
        return new self('Cette invitation a expiré ou a déjà été utilisée. Demandez-en une nouvelle.', 'invitation_expired', 410);
    }
}
