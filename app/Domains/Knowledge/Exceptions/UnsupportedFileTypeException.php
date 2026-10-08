<?php

namespace App\Domains\Knowledge\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Aucun extracteur de texte ne sait lire ce type de fichier. */
final class UnsupportedFileTypeException extends DomainException
{
    public static function make(string $mimeType): self
    {
        return new self(
            "Type de fichier non pris en charge : {$mimeType}.",
            'unsupported_file_type',
            422,
            ['mime_type' => $mimeType],
        );
    }
}
