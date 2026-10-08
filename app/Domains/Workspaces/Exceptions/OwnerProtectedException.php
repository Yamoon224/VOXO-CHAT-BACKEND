<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Le propriétaire répond de l'espace : son adhésion ne se modifie pas par la gestion d'équipe.
 */
final class OwnerProtectedException extends DomainException
{
    public static function make(): self
    {
        return new self('Le propriétaire d\'un espace de travail ne peut être ni rétrogradé ni retiré.', 'owner_protected', 409);
    }
}
