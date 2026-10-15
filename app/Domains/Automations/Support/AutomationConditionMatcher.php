<?php

namespace App\Domains\Automations\Support;

use App\Domains\Automations\Enums\AutomationConditionOperator;

/**
 * Évaluateur des conditions d'un scénario (section 2.8) — même logique que
 * `RoutingExecutionService`, partagée ici entre l'exécution réelle et le test
 * à blanc (`AutomationTestService`) pour ne jamais diverger entre les deux.
 */
final class AutomationConditionMatcher
{
    /** @param  list<array{field: string, operator: string, value: string}>  $conditions */
    public static function matches(array $conditions, ?string $channel, string $messageBody): bool
    {
        foreach ($conditions as $condition) {
            if (! self::matchesOne($condition, $channel, $messageBody)) {
                return false;
            }
        }

        return true;
    }

    /** @param  array{field: string, operator: string, value: string}  $condition */
    private static function matchesOne(array $condition, ?string $channel, string $messageBody): bool
    {
        $value = match ($condition['field']) {
            'channel' => $channel,
            'message' => $messageBody,
            default => null,
        };

        if (! is_string($value)) {
            return false;
        }

        $target = $condition['value'];

        return match (AutomationConditionOperator::from($condition['operator'])) {
            AutomationConditionOperator::Equals => strcasecmp($value, $target) === 0,
            AutomationConditionOperator::Contains => str_contains(mb_strtolower($value), mb_strtolower($target)),
        };
    }
}
