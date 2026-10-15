<?php

namespace App\Domains\Conversations\Enums;

/** Signaux disponibles pour une règle de routage (section 2.4 : origine, intention). */
enum RoutingConditionField: string
{
    case Channel = 'channel';
    case Message = 'message';

    public function label(): string
    {
        return match ($this) {
            self::Channel => 'Canal (origine)',
            self::Message => 'Premier message (intention)',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
