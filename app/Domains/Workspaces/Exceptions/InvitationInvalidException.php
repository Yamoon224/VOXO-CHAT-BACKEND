<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Le jeton ne correspond à aucune invitation : lien tronqué, ou invitation révoquée.
 */
final class InvitationInvalidException extends DomainException
{
    public static function make(): self
    {
        return new self('Cette invitation est introuvable.', 'invitation_invalid', 404);
    }
}
