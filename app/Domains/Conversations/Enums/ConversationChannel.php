<?php

namespace App\Domains\Conversations\Enums;

/**
 * `email` et `contact_form` arrivent avec le domaine `Channels` (lot 4) sans
 * changer `Conversations`/`Assistant` au-delà de ce cas — voir section 2.5 du
 * cahier des charges. `ticket` arrive avec le domaine `Tickets` (lot 4,
 * section 2.7) : une demande ouverte depuis le portail client, sans passer
 * par un des trois canaux ci-dessus. `whatsapp`, `messenger`, `instagram`,
 * `telegram` et `sms` arrivent avec le lot 5 (Omnicanal, section 2.5, P2) —
 * même raisonnement, un connecteur de plus derrière `Channels`. `x`, `viber`,
 * `line` et `telephony` arrivent avec le lot 7 (section 2.5, P3) — même
 * raisonnement encore, `telephony` étant un journal d'appels (pas de
 * téléphonie temps réel).
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
    case X = 'x';
    case Viber = 'viber';
    case Line = 'line';
    case Telephony = 'telephony';

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
            self::X => 'X',
            self::Viber => 'Viber',
            self::Line => 'Line',
            self::Telephony => 'Téléphonie',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
