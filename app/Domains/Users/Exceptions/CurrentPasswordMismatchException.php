<?php

namespace App\Domains\Users\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

final class CurrentPasswordMismatchException extends DomainException
{
    public static function make(): self
    {
        return new self('Le mot de passe actuel est incorrect.', 'current_password_mismatch', 422);
    }
}
