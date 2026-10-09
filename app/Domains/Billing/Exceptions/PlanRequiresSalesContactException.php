<?php

namespace App\Domains\Billing\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** L'offre grands comptes se négocie, elle ne se souscrit pas en self-service. */
final class PlanRequiresSalesContactException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'Ce palier se négocie directement avec notre équipe, contactez-nous.',
            'plan_requires_sales_contact',
            422,
        );
    }
}
