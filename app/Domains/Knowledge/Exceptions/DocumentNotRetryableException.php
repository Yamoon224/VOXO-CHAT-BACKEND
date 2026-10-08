<?php

namespace App\Domains\Knowledge\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Seul un document en échec peut être redéclenché. */
final class DocumentNotRetryableException extends DomainException
{
    public static function make(): self
    {
        return new self(
            "Ce document n'est pas en échec : rien à redéclencher.",
            'document_not_retryable',
            409,
        );
    }
}
