<?php

namespace App\Domains\Assistant\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Le palier de l'espace de travail ne permet pas une génération IA de plus ce mois-ci. */
final class AiCreditQuotaExceededException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'Le quota de crédits IA de ce mois est atteint. Changez de palier pour en obtenir plus.',
            'ai_credit_quota_exceeded',
            409,
        );
    }
}
