<?php

namespace App\Domains\Knowledge\Enums;

/**
 * Origine d'une source de connaissance.
 */
enum KnowledgeSourceType: string
{
    case Upload = 'upload';
    case Website = 'website';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Upload => 'Fichiers importés',
            self::Website => 'Site web exploré',
            self::Manual => 'Entrées manuelles',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
