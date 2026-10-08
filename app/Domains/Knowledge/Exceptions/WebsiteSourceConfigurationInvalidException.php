<?php

namespace App\Domains\Knowledge\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Une source de type site web exige une URL de départ. */
final class WebsiteSourceConfigurationInvalidException extends DomainException
{
    public static function make(): self
    {
        return new self(
            "L'exploration d'un site web exige une URL de départ.",
            'website_source_configuration_invalid',
            422,
        );
    }
}
