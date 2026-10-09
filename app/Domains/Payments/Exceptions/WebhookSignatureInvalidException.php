<?php

namespace App\Domains\Payments\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** La signature d'un rappel de paiement ne correspond pas au corps reçu. */
final class WebhookSignatureInvalidException extends DomainException
{
    public static function make(): self
    {
        return new self('Signature de rappel invalide.', 'webhook_signature_invalid', 400);
    }
}
