<?php

namespace App\Domains\Knowledge\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Une entrée manuelle exige une question et une réponse non vides. */
final class QaContentRequiredException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'La question et la réponse sont obligatoires.',
            'qa_content_required',
            422,
        );
    }
}
