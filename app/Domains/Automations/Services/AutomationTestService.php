<?php

namespace App\Domains\Automations\Services;

use App\Domains\Automations\Contracts\AutomationScenarioRepositoryContract;
use App\Domains\Automations\Enums\AutomationActionType;
use App\Domains\Automations\Support\AutomationConditionMatcher;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;

/**
 * « Brouillon et test avant publication » (section 2.8) : simule un scénario
 * sur un message d'exemple sans jamais toucher une vraie conversation, un
 * contact ou un agent — même raisonnement que `AssistantService::sandboxRespond()`
 * pour l'agent IA (lot 2), transposé aux scénarios.
 */
final class AutomationTestService
{
    public function __construct(
        private readonly AutomationScenarioRepositoryContract $scenarios,
        private readonly MembershipReaderContract $memberships,
    ) {}

    /** @return array{conditions_matched: bool, steps: list<array{type: string, preview: string}>} */
    public function test(WorkspaceScope $scope, string $scenarioId, string $sampleMessage, ?string $sampleChannel): array
    {
        $scenario = $this->scenarios->findInWorkspaceOrFail($scope->workspaceId, $scenarioId);
        $matched = AutomationConditionMatcher::matches($scenario->conditions, $sampleChannel, $sampleMessage);

        $steps = [];

        foreach ($scenario->steps as $index => $step) {
            $type = AutomationActionType::from($step['type']);
            $steps[] = ['type' => $type->value, 'preview' => $this->preview($scope->workspaceId, $type, $step['config'])];

            if ($scenario->hasAskQuestionStepAt($index)) {
                break; // Rien à simuler après : il faudrait une vraie réponse du visiteur.
            }
        }

        return ['conditions_matched' => $matched, 'steps' => $steps];
    }

    /** @param  array<string, mixed>  $config */
    private function preview(string $workspaceId, AutomationActionType $type, array $config): string
    {
        return match ($type) {
            AutomationActionType::SendMessage => "Envoie : « {$config['body']} »",
            AutomationActionType::AskQuestion => "Demande : « {$config['body']} », puis attend la réponse pour renseigner « {$config['attribute_key']} » et reprend ensuite.",
            AutomationActionType::AssignUser => 'Affecte à '.$this->memberName($workspaceId, (string) $config['user_id']),
            AutomationActionType::SetContactAttribute => "Définit l'attribut « {$config['key']} » à « {$config['value']} » sur le contact.",
        };
    }

    private function memberName(string $workspaceId, string $userId): string
    {
        $membership = $this->memberships->findForUser($workspaceId, $userId);

        return $membership?->user->name ?? "un agent introuvable ({$userId})";
    }
}
