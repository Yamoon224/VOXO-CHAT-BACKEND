<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * L'invité n'a pas encore de compte et n'a pas fourni de quoi en créer un.
 */
final class AccountDetailsRequiredException extends DomainException
{
    public static function make(): self
    {
        return new self('Indiquez votre nom et choisissez un mot de passe pour créer votre compte.', 'account_details_required', 422);
    }
}
