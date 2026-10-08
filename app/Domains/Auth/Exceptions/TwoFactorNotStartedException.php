<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Confirmation demandée sans secret préalablement généré.
 */
final class TwoFactorNotStartedException extends DomainException
{
    public static function make(): self
    {
        return new self('Aucune activation de la double authentification n\'est en cours. Recommencez depuis le début.', 'two_factor_not_started', 409);
    }
}
