<?php

namespace App\Domains\Assistant\Enums;

/** Où un paramètre atterrit dans la requête HTTP réelle (section 2.2, P3) — voir `AgentToolCaller`. */
enum AgentToolParameterLocation: string
{
    case Path = 'path';
    case Query = 'query';
    case Body = 'body';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
