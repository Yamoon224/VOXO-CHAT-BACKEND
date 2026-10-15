<?php

namespace App\Domains\Automations\Enums;

/**
 * « Actions » du cahier des charges (section 2.8), exécutées dans l'ordre où
 * elles apparaissent dans `AutomationScenario::steps`. `AskQuestion` est la
 * seule qui interrompt l'exécution : elle envoie sa question puis attend le
 * prochain message du visiteur comme réponse (« collecte de champs dans le
 * chat ») avant de reprendre à l'étape suivante — voir `AutomationRun`.
 */
enum AutomationActionType: string
{
    case SendMessage = 'send_message';
    case AskQuestion = 'ask_question';
    case AssignUser = 'assign_user';
    case SetContactAttribute = 'set_contact_attribute';

    public function label(): string
    {
        return match ($this) {
            self::SendMessage => 'Envoyer un message',
            self::AskQuestion => 'Poser une question et attendre la réponse',
            self::AssignUser => 'Affecter à un agent',
            self::SetContactAttribute => 'Définir un attribut du contact',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
