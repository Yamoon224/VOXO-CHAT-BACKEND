<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Jeton de réinitialisation inconnu, expiré ou déjà utilisé.
 */
final class InvalidResetTokenException extends DomainException
{
    public static function make(): self
    {
        return new self('Ce lien de réinitialisation est invalide ou a expiré. Demandez-en un nouveau.', 'invalid_reset_token', 422);
    }
}
