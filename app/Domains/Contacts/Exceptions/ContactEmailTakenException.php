<?php

namespace App\Domains\Contacts\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Un autre contact de cet espace porte déjà cette adresse. */
final class ContactEmailTakenException extends DomainException
{
    public static function make(): self
    {
        return new self('Un contact utilise déjà cette adresse e-mail.', 'contact_email_taken', 409);
    }
}
