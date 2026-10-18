<?php

namespace App\Domains\StatusPage\Enums;

enum IncidentImpact: string
{
    case None = 'none';
    case Minor = 'minor';
    case Major = 'major';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucun',
            self::Minor => 'Mineur',
            self::Major => 'Majeur',
            self::Critical => 'Critique',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
