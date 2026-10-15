<?php

namespace App\Domains\Channels\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Un canal ne peut pas être activé sans les identifiants que son pilote exige. */
final class ChannelConnectionIncompleteException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'Ce canal ne peut pas être activé : des identifiants sont manquants.',
            'channel_connection_incomplete',
            422,
        );
    }
}
