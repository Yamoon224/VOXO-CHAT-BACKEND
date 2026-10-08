<?php

namespace App\Domains\Conversations\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Une conversation ne peut être affectée qu'à un membre de son espace de travail. */
final class AssigneeNotMemberException extends DomainException
{
    public static function make(): self
    {
        return new self("Cette personne n'est pas membre de l'espace de travail.", 'assignee_not_member', 422);
    }
}
