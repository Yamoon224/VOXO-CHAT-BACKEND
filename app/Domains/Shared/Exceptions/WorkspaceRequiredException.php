<?php

namespace App\Domains\Shared\Exceptions;

/**
 * Le jeton de l'appelant ne désigne aucun espace de travail utilisable : il
 * n'en a jamais porté, ou l'appelant n'en est plus membre.
 */
final class WorkspaceRequiredException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'Aucun espace de travail actif. Sélectionnez un espace pour continuer.',
            'workspace_required',
            403,
        );
    }
}
