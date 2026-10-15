<?php

namespace App\Domains\Conversations\Enums;

/**
 * `email` et `contact_form` arrivent avec le domaine `Channels` (lot 4) sans
 * changer `Conversations`/`Assistant` au-delà de ce cas — voir section 2.5 du
 * cahier des charges. `ticket` arrive avec le domaine `Tickets` (lot 4,
 * section 2.7) : une demande ouverte depuis le portail client, sans passer
 * par un des trois canaux ci-dessus. `whatsapp`, `messenger`, `instagram`,
 * `telegram` et `sms` arrivent avec le lot 5 (Omnicanal, section 2.5, P2) —
 * même raisonnement, un connecteur de plus derrière `Channels`.
 */
enum ConversationChannel: string
{
    case Widget = 'widget';
    case Email = 'email';
    case ContactForm = 'contact_form';
    case Ticket = 'ticket';
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
    case Telegram = 'telegram';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Widget => 'Widget',
            self::Email => 'E-mail',
            self::ContactForm => 'Formulaire de contact',
            self::Ticket => 'Ticket',
            self::WhatsApp => 'WhatsApp',
            self::Messenger => 'Messenger',
            self::Instagram => 'Instagram',
            self::Telegram => 'Telegram',
            self::Sms => 'SMS',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
