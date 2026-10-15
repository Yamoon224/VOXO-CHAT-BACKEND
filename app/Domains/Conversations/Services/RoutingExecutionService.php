<?php

namespace App\Domains\Conversations\Services;

use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\RoutingRuleRepositoryContract;
use App\Domains\Conversations\Enums\RoutingActionType;
use App\Domains\Conversations\Enums\RoutingConditionOperator;
use App\Models\Conversation;
use App\Models\RoutingRule;

/**
 * Affecte une conversation qui vient de s'ouvrir, selon la première règle
 * active qui correspond (section 2.4 : tourniquet, origine, intention) — pas
 * de cumul, une seule règle agit. Sans règle correspondante, la conversation
 * reste non affectée comme avant.
 */
final class RoutingExecutionService
{
    public function __construct(
        private readonly RoutingRuleRepositoryContract $rules,
        private readonly ConversationRepositoryContract $conversations,
    ) {}

    public function applyTo(Conversation $conversation, string $firstMessageBody): void
    {
        foreach ($this->rules->activeForWorkspace($conversation->workspace_id) as $rule) {
            if ($this->matches($rule, $conversation, $firstMessageBody)) {
                $this->assign($rule, $conversation);

                return;
            }
        }
    }

    /** Une règle sans condition est un filet par défaut : elle correspond toujours. */
    private function matches(RoutingRule $rule, Conversation $conversation, string $firstMessageBody): bool
    {
        foreach ($rule->conditions as $condition) {
            if (! $this->matchesCondition($condition, $conversation, $firstMessageBody)) {
                return false;
            }
        }

        return true;
    }

    /** @param  array{field: string, operator: string, value: string}  $condition */
    private function matchesCondition(array $condition, Conversation $conversation, string $firstMessageBody): bool
    {
        $value = match ($condition['field']) {
            'channel' => $conversation->channel->value,
            'message' => $firstMessageBody,
            default => null,
        };

        if (! is_string($value)) {
            return false;
        }

        $target = $condition['value'];

        return match (RoutingConditionOperator::from($condition['operator'])) {
            RoutingConditionOperator::Equals => strcasecmp($value, $target) === 0,
            RoutingConditionOperator::Contains => str_contains(mb_strtolower($value), mb_strtolower($target)),
        };
    }

    private function assign(RoutingRule $rule, Conversation $conversation): void
    {
        $assigneeId = match ($rule->action_type) {
            RoutingActionType::AssignUser => $rule->assignee_user_ids[0] ?? null,
            RoutingActionType::RoundRobin => $this->nextRoundRobinAssignee($rule),
        };

        if ($assigneeId !== null) {
            $this->conversations->update($conversation, ['assigned_user_id' => $assigneeId]);
        }
    }

    private function nextRoundRobinAssignee(RoutingRule $rule): ?string
    {
        $pool = $rule->assignee_user_ids;

        if ($pool === []) {
            return null;
        }

        $index = $rule->next_round_robin_index % count($pool);
        $this->rules->update($rule, ['next_round_robin_index' => $index + 1]);

        return $pool[$index];
    }
}
