<?php

namespace App\Domains\Campaigns\Enums;

enum CampaignSendStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Sent => 'Envoyé',
            self::Failed => 'Échoué',
            self::Skipped => 'Ignoré',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
