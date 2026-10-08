<?php

namespace App\Domains\Conversations\Enums;

enum MessageSenderType: string
{
    case Visitor = 'visitor';
    case Agent = 'agent';
    case Ai = 'ai';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Visitor => 'Visiteur',
            self::Agent => 'Agent',
            self::Ai => 'Agent IA',
            self::System => 'Système',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
