<?php

namespace App\Domains\Knowledge\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Le palier de l'espace de travail ne permet pas d'indexer un document de plus. */
final class DocumentQuotaExceededException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'Le nombre de documents indexables du palier actuel est atteint. Changez de palier pour en indexer plus.',
            'document_quota_exceeded',
            409,
        );
    }
}
