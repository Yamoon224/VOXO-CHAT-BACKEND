<?php

namespace App\Domains\Widget\Enums;

enum WidgetPosition: string
{
    case BottomRight = 'bottom_right';
    case BottomLeft = 'bottom_left';

    public function label(): string
    {
        return match ($this) {
            self::BottomRight => 'En bas à droite',
            self::BottomLeft => 'En bas à gauche',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
