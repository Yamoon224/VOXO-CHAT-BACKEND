<?php

namespace App\Domains\Conversations\Enums;

/**
 * Un seul canal câblé pour l'instant. Chaque canal futur (`email`,
 * `contact_form`, WhatsApp...) arrive derrière son propre connecteur, sans
 * changer les domaines `Conversations`/`Assistant` — voir section 2.5 du
 * cahier des charges.
 */
enum ConversationChannel: string
{
    case Widget = 'widget';

    public function label(): string
    {
        return match ($this) {
            self::Widget => 'Widget',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
