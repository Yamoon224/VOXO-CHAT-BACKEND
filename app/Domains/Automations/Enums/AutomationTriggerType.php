<?php

namespace App\Domains\Automations\Enums;

/** « Déclencheur » du cahier des charges (section 2.8). */
enum AutomationTriggerType: string
{
    /** Ne joue qu'à l'ouverture de la conversation — jamais sur un message suivant. */
    case ConversationOpened = 'conversation_opened';

    /** Joue sur chaque message du visiteur, nouvelle conversation ou non. */
    case MessageReceived = 'message_received';

    public function label(): string
    {
        return match ($this) {
            self::ConversationOpened => 'Conversation ouverte',
            self::MessageReceived => 'Message reçu',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
