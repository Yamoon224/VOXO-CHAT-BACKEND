<?php

namespace App\Domains\Payments\Enums;

enum InvoiceStatus: string
{
    case Open = 'open';
    case Paid = 'paid';
    case Void = 'void';
    case Uncollectible = 'uncollectible';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'En attente',
            self::Paid => 'Payée',
            self::Void => 'Annulée',
            self::Uncollectible => 'Impayée',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
