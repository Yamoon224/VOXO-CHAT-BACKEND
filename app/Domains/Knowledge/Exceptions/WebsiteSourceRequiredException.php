<?php

namespace App\Domains\Knowledge\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** La ré-indexation manuelle ne s'applique qu'aux sources de type site web. */
final class WebsiteSourceRequiredException extends DomainException
{
    public static function make(): self
    {
        return new self(
            "Seule une source de type site web peut être ré-explorée.",
            'website_source_required',
            422,
        );
    }
}
