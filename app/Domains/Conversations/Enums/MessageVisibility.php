<?php

namespace App\Domains\Conversations\Enums;

enum MessageVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Visible du visiteur',
            self::Internal => "Note interne de l'équipe",
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
