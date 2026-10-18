<?php

namespace App\Domains\Campaigns\Enums;

/**
 * Canaux de campagne (section 2.9) : e-mail et chat d'abord, WhatsApp et SMS
 * suivront derrière le même contrat — voir `CampaignDispatchService`.
 */
enum CampaignChannel: string
{
    case Email = 'email';
    case Chat = 'chat';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'E-mail',
            self::Chat => 'Chat',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
