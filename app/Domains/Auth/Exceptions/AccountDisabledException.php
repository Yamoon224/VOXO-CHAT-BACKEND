<?php

namespace App\Domains\Auth\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Un compte désactivé garde son historique mais perd l'accès.
 */
final class AccountDisabledException extends DomainException
{
    public static function make(): self
    {
        return new self('Ce compte est désactivé. Contactez l\'administrateur de votre espace.', 'account_disabled', 403);
    }
}
