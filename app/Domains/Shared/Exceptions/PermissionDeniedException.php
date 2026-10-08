<?php

namespace App\Domains\Shared\Exceptions;

/**
 * Le rôle de l'appelant dans l'espace de travail courant ne lui accorde pas
 * la permission exigée par la route.
 */
final class PermissionDeniedException extends DomainException
{
    public static function forPermission(string $permission): self
    {
        return new self(
            "Vous n'avez pas les droits nécessaires pour cette action.",
            'forbidden',
            403,
            ['required_permission' => $permission],
        );
    }
}
