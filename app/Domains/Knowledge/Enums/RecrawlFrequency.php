<?php

namespace App\Domains\Knowledge\Enums;

/**
 * Cadence de ré-indexation planifiée d'une source de type site web.
 */
enum RecrawlFrequency: string
{
    case Manual = 'manual';
    case Daily = 'daily';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manuelle',
            self::Daily => 'Quotidienne',
            self::Weekly => 'Hebdomadaire',
        };
    }

    /** Intervalle entre deux explorations planifiées, `null` si manuelle (jamais automatique). */
    public function intervalInHours(): ?int
    {
        return match ($this) {
            self::Manual => null,
            self::Daily => 24,
            self::Weekly => 24 * 7,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
