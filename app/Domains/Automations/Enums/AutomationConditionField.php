<?php

namespace App\Domains\Automations\Enums;

/**
 * Signaux disponibles pour les conditions d'un scénario (section 2.8) — même
 * principe que `RoutingConditionField`, délibérément dupliqué plutôt que
 * partagé : les deux domaines ne doivent pas dépendre l'un de l'autre pour
 * une forme aussi simple.
 */
enum AutomationConditionField: string
{
    case Channel = 'channel';
    case Message = 'message';

    public function label(): string
    {
        return match ($this) {
            self::Channel => 'Canal (origine)',
            self::Message => 'Message',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
