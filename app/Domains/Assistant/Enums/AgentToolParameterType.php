<?php

namespace App\Domains\Assistant\Enums;

/** Types JSON Schema exposés à Claude pour un paramètre d'outil (section 2.2, P3). */
enum AgentToolParameterType: string
{
    case String = 'string';
    case Number = 'number';
    case Boolean = 'boolean';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
