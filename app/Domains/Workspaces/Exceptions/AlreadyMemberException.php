<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * L'adresse invitée correspond à un compte déjà membre de l'espace.
 */
final class AlreadyMemberException extends DomainException
{
    public static function make(): self
    {
        return new self('Cette personne fait déjà partie de l\'espace de travail.', 'already_member', 409);
    }
}
