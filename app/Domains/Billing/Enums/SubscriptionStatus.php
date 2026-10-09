<?php

namespace App\Domains\Billing\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => "À l'essai",
            self::Active => 'Actif',
            self::PastDue => 'Paiement en retard',
            self::Canceled => 'Résilié',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
