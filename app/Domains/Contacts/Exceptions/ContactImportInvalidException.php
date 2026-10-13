<?php

namespace App\Domains\Contacts\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Fichier CSV sans en-tête `email` exploitable, ou vide. */
final class ContactImportInvalidException extends DomainException
{
    public static function missingEmailColumn(): self
    {
        return new self(
            'Le fichier doit comporter une colonne « email » dans son en-tête.',
            'contact_import_missing_email_column',
            422,
        );
    }

    public static function empty(): self
    {
        return new self('Le fichier ne contient aucune ligne à importer.', 'contact_import_empty', 422);
    }
}
