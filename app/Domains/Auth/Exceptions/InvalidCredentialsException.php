<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Message identique que le compte existe ou non : distinguer les deux cas ferait du formulaire un oracle d'énumération de comptes.
 */
final class InvalidCredentialsException extends DomainException
{
    public static function make(): self
    {
        return new self('Identifiants invalides.', 'invalid_credentials', 422);
    }
}
