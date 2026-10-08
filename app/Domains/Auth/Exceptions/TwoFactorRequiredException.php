<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Le mot de passe est correct mais le compte exige un second facteur : le client affiche alors le champ du code.
 */
final class TwoFactorRequiredException extends DomainException
{
    public static function make(): self
    {
        return new self('Saisissez le code de votre application d\'authentification.', 'two_factor_required', 422);
    }
}
