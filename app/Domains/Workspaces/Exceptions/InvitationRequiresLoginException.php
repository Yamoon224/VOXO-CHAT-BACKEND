<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * L'adresse invitée a déjà un compte : la possession du lien ne suffit pas à agir en son nom.
 */
final class InvitationRequiresLoginException extends DomainException
{
    public static function make(): self
    {
        return new self('Un compte existe déjà pour cette adresse. Connectez-vous pour accepter l\'invitation.', 'login_required', 409);
    }
}
