<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Le mot de passe demandé pour confirmer une action sensible ne correspond pas.
 */
final class InvalidPasswordException extends DomainException
{
    public static function make(): self
    {
        return new self('Mot de passe incorrect.', 'invalid_password', 422);
    }
}
