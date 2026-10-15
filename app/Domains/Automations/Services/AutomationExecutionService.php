<?php

namespace App\Domains\Automations\Services;

use App\Domains\Automations\Contracts\AutomationResponderContract;
use App\Domains\Automations\Contracts\AutomationRunRepositoryContract;
use App\Domains\Automations\Contracts\AutomationScenarioRepositoryContract;
use App\Domains\Automations\Enums\AutomationActionType;
use App\Domains\Automations\Enums\AutomationRunStatus;
use App\Domains\Automations\Enums\AutomationTriggerType;
use App\Domains\Automations\Support\AutomationConditionMatcher;
use App\Domains\Contacts\Contracts\ContactLinkContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Models\AutomationRun;
use App\Models\AutomationScenario;
use App\Models\Conversation;

/**
 * Évalue et exécute les scénarios d'automatisation (section 2.8) sur chaque
 * message entrant — appelée depuis `ConversationService::receiveChannelMessage()`,
 * avant l'agent IA : un scénario qui prend la main sur un message remplace sa
 * réponse automatique pour ce tour-là (voir la valeur de retour).
 */
final class AutomationExecutionService implements AutomationResponderContract
{
    public function __construct(
        private readonly AutomationScenarioRepositoryContract $scenarios,
        private readonly AutomationRunRepositoryContract $runs,
        private readonly ConversationRepositoryContract $conversations,
        private readonly MessageRepositoryContract $messages,
        private readonly ContactLinkContract $contacts,
    ) {}

    /** @return bool `true` si un scénario a pris en charge ce message (nouvelle exécution démarrée ou reprise) — l'appelant ne doit alors pas aussi faire répondre l'agent IA. */
    public function applyTo(Conversation $conversation, string $messageBody, bool $isNewConversation): bool
    {
        $run = $this->runs->findActiveForConversation($conversation->id);

        if ($run !== null) {
            $this->resume($run, $conversation, $messageBody);

            return true;
        }

        $scenario = $this->matchScenario($conversation, $messageBody, $isNewConversation);

        if ($scenario === null) {
            return false;
        }

        $run = $this->runs->create([
            'workspace_id' => $conversation->workspace_id,
            'conversation_id' => $conversation->id,
            'scenario_id' => $scenario->id,
            'current_step' => 0,
            'status' => AutomationRunStatus::Running,
        ]);
        $this->execute($run, $scenario, $conversation, 0);

        return true;
    }

    private function matchScenario(Conversation $conversation, string $messageBody, bool $isNewConversation): ?AutomationScenario
    {
        foreach ($this->scenarios->publishedForWorkspace($conversation->workspace_id) as $scenario) {
            if ($scenario->trigger_type === AutomationTriggerType::ConversationOpened && ! $isNewConversation) {
                continue;
            }

            if (AutomationConditionMatcher::matches($scenario->conditions, $conversation->channel->value, $messageBody)) {
                return $scenario;
            }
        }

        return null;
    }

    private function resume(AutomationRun $run, Conversation $conversation, string $messageBody): void
    {
        // Jamais `$run->scenario` (relation Eloquent) : ce service ne doit
        // atteindre un autre agrégat qu'à travers son dépôt, jamais une
        // relation implicite — même principe que partout ailleurs ici.
        $scenario = $this->scenarios->findInWorkspaceOrFail($conversation->workspace_id, $run->scenario_id);
        $pausedStep = $scenario->steps[$run->current_step - 1];

        $conversation = $this->setContactAttribute($conversation, $pausedStep['config']['attribute_key'], $messageBody);

        $this->execute($run, $scenario, $conversation, $run->current_step);
    }

    private function execute(AutomationRun $run, AutomationScenario $scenario, Conversation $conversation, int $fromStep): void
    {
        $steps = $scenario->steps;

        for ($index = $fromStep; $index < count($steps); $index++) {
            $step = $steps[$index];
            $conversation = $this->runStep($conversation, AutomationActionType::from($step['type']), $step['config']);

            if ($scenario->hasAskQuestionStepAt($index)) {
                $this->runs->update($run, ['current_step' => $index + 1]);

                return;
            }
        }

        $this->runs->update($run, ['status' => AutomationRunStatus::Completed]);
    }

    /** @param  array<string, mixed>  $config */
    private function runStep(Conversation $conversation, AutomationActionType $type, array $config): Conversation
    {
        return match ($type) {
            AutomationActionType::SendMessage, AutomationActionType::AskQuestion => $this->sendMessage($conversation, (string) $config['body']),
            AutomationActionType::AssignUser => $this->conversations->update($conversation, ['assigned_user_id' => $config['user_id']]),
            AutomationActionType::SetContactAttribute => $this->setContactAttribute($conversation, (string) $config['key'], (string) $config['value']),
        };
    }

    private function sendMessage(Conversation $conversation, string $body): Conversation
    {
        $this->messages->recordSystemNotice($conversation->id, $conversation->workspace_id, $body);

        return $conversation;
    }

    private function setContactAttribute(Conversation $conversation, string $key, string $value): Conversation
    {
        $contactId = $this->contacts->setCustomAttributeForContact($conversation->workspace_id, $conversation->contact_id, $key, $value);

        if ($conversation->contact_id === $contactId) {
            return $conversation;
        }

        return $this->conversations->update($conversation, ['contact_id' => $contactId]);
    }
}
