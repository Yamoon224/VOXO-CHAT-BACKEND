<?php

namespace App\Domains\Automations\Enums;

enum AutomationRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
