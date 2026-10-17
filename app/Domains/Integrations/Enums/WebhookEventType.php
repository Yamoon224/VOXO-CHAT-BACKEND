<?php

namespace App\Domains\Integrations\Enums;

/**
 * Catalogue des événements auxquels un abonnement webhook peut s'abonner
 * (section 2.14, lot 6) — volontairement réduit à ce que les domaines
 * existants déclenchent déjà ; grandira sans changer le schéma des abonnements.
 */
enum WebhookEventType: string
{
    case ConversationCreated = 'conversation.created';
    case ConversationResolved = 'conversation.resolved';
    case MessageCreated = 'message.created';

    public function label(): string
    {
        return match ($this) {
            self::ConversationCreated => 'Conversation créée',
            self::ConversationResolved => 'Conversation résolue',
            self::MessageCreated => 'Message créé',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
