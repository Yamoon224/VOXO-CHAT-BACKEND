<?php

namespace App\Domains\Billing\Enums;

/** Dimensions de quota surveillées par `QuotaAlertService` (section 2.13). */
enum QuotaType: string
{
    case Seats = 'seats';
    case Documents = 'documents';
    case AiCredits = 'ai_credits';

    public function label(): string
    {
        return match ($this) {
            self::Seats => 'sièges',
            self::Documents => 'documents de la base de connaissances',
            self::AiCredits => 'crédits IA',
        };
    }
}
