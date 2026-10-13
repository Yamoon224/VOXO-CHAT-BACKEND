<?php

namespace App\Domains\Billing\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Un palier auquel au moins un abonnement est rattaché ne se supprime pas. */
final class PlanInUseException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'Ce palier est utilisé par au moins un abonnement, il ne peut pas être supprimé.',
            'plan_in_use',
            409,
        );
    }
}
