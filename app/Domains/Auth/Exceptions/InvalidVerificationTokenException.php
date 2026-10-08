<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Jeton de vérification d'adresse falsifié, expiré, ou émis pour une adresse qui a changé depuis.
 */
final class InvalidVerificationTokenException extends DomainException
{
    public static function make(): self
    {
        return new self('Ce lien de vérification est invalide ou a expiré. Demandez-en un nouveau.', 'invalid_verification_token', 422);
    }
}
