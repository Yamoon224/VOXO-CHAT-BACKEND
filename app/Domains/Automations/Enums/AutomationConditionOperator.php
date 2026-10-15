<?php

namespace App\Domains\Automations\Enums;

enum AutomationConditionOperator: string
{
    case Equals = 'equals';
    case Contains = 'contains';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'est égal à',
            self::Contains => 'contient',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
