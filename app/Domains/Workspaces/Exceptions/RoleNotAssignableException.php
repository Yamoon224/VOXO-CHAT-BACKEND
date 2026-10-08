<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Le rôle de propriétaire ne s'attribue ni par invitation ni par changement de rôle.
 */
final class RoleNotAssignableException extends DomainException
{
    public static function make(): self
    {
        return new self('Ce rôle ne peut pas être attribué.', 'role_not_assignable', 422);
    }
}
