<?php

namespace App\Domains\Billing\Enums;

enum AiCreditTransactionType: string
{
    case Grant = 'grant';
    case Usage = 'usage';
    case Topup = 'topup';

    public function label(): string
    {
        return match ($this) {
            self::Grant => 'Dotation de la période',
            self::Usage => 'Conversation automatisée',
            self::Topup => 'Recharge',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
