<?php

namespace App\Domains\Conversations\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Un espace de travail ne garde qu'une réponse pré-enregistrée par titre. */
final class CannedResponseTitleTakenException extends DomainException
{
    public static function make(): self
    {
        return new self('Une réponse pré-enregistrée porte déjà ce titre.', 'canned_response_title_taken', 409);
    }
}
