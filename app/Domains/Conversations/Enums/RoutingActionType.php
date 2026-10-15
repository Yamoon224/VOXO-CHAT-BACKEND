<?php

namespace App\Domains\Conversations\Enums;

/** « Tourniquet » du cahier des charges (section 2.4) = `RoundRobin`. */
enum RoutingActionType: string
{
    case AssignUser = 'assign_user';
    case RoundRobin = 'round_robin';

    public function label(): string
    {
        return match ($this) {
            self::AssignUser => 'Affecter à un agent',
            self::RoundRobin => 'Répartir au tourniquet',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
