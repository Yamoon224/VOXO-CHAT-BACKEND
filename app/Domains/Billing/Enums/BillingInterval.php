<?php

namespace App\Domains\Billing\Enums;

enum BillingInterval: string
{
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return match ($this) {
            self::Month => 'Mensuel',
            self::Year => 'Annuel',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
