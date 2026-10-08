<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Le code TOTP fourni ne correspond pas au secret du compte.
 */
final class InvalidTwoFactorCodeException extends DomainException
{
    public static function make(): self
    {
        return new self('Code de vérification invalide.', 'invalid_two_factor_code', 422);
    }
}
