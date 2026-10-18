<?php

namespace App\Domains\StatusPage\Enums;

enum IncidentStatus: string
{
    case Investigating = 'investigating';
    case Identified = 'identified';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Investigating => 'En investigation',
            self::Identified => 'Cause identifiée',
            self::Monitoring => 'Sous surveillance',
            self::Resolved => 'Résolu',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
