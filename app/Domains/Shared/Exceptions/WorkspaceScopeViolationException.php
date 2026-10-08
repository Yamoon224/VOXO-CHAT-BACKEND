<?php

namespace App\Domains\Shared\Exceptions;

/**
 * L'appelant a désigné un espace de travail dont il n'est pas membre.
 */
final class WorkspaceScopeViolationException extends DomainException
{
    public static function make(): self
    {
        return new self(
            "Vous n'avez pas accès à cet espace de travail.",
            'workspace_scope_violation',
            403,
        );
    }
}
