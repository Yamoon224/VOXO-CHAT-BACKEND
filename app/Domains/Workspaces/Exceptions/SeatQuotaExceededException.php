<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Le palier de l'espace de travail ne permet pas d'ajouter un membre de plus. */
final class SeatQuotaExceededException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'Le nombre de places du palier actuel est atteint. Changez de palier pour en ajouter.',
            'seat_quota_exceeded',
            409,
        );
    }
}
