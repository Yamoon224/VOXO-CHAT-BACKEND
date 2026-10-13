<?php

namespace App\Domains\Contacts\Enums;

/** Opérateurs d'un critère de segment (section 2.6) — volontairement simples, pas un DSL de requête. */
enum SegmentOperator: string
{
    case Equals = 'equals';
    case Contains = 'contains';
    case IsSet = 'is_set';
    case IsNotSet = 'is_not_set';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
