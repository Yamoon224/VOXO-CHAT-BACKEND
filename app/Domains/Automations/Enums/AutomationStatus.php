<?php

namespace App\Domains\Automations\Enums;

/** Brouillon et test avant publication (section 2.8) : seul un scénario publié est évalué sur les conversations réelles. */
enum AutomationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
