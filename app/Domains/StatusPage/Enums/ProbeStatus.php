<?php

namespace App\Domains\StatusPage\Enums;

/** Statut d'une sonde de disponibilité (section 2.11) — `unknown` jusqu'à la toute première vérification. */
enum ProbeStatus: string
{
    case Unknown = 'unknown';
    case Up = 'up';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Inconnu',
            self::Up => 'Opérationnel',
            self::Down => 'Interrompu',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
