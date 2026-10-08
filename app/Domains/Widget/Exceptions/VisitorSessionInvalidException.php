<?php

namespace App\Domains\Widget\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Jeton de session de visiteur absent, falsifié, expiré, ou d'un autre espace de travail. */
final class VisitorSessionInvalidException extends DomainException
{
    public static function make(): self
    {
        return new self('Session de visiteur invalide ou expirée.', 'visitor_session_invalid', 401);
    }
}
