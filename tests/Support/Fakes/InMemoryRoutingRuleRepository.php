<?php

namespace Tests\Support\Fakes;

use App\Domains\Conversations\Contracts\RoutingRuleRepositoryContract;
use App\Models\RoutingRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemoryRoutingRuleRepository implements RoutingRuleRepositoryContract
{
    /** @var array<string, RoutingRule> */
    private array $rules = [];

    public function __construct(RoutingRule ...$rules)
    {
        foreach ($rules as $rule) {
            $this->rules[$rule->id] = $rule;
        }
    }

    public function forWorkspace(string $workspaceId): Collection
    {
        return $this->scoped($workspaceId);
    }

    public function activeForWorkspace(string $workspaceId): Collection
    {
        return $this->scoped($workspaceId)->filter(fn (RoutingRule $rule) => $rule->is_active)->values();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): RoutingRule
    {
        $rule = $this->rules[$id] ?? null;

        if ($rule === null || $rule->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $rule;
    }

    public function create(array $attributes): RoutingRule
    {
        $rule = ModelFactory::routingRule($attributes);

        return $this->rules[$rule->id] = $rule;
    }

    public function update(RoutingRule $rule, array $attributes): RoutingRule
    {
        foreach (['conditions', 'assignee_user_ids'] as $jsonField) {
            if (array_key_exists($jsonField, $attributes) && is_array($attributes[$jsonField])) {
                $attributes[$jsonField] = json_encode($attributes[$jsonField]);
            }
        }

        $rule->setRawAttributes($attributes + $rule->getAttributes(), true);

        return $rule;
    }

    public function delete(RoutingRule $rule): void
    {
        unset($this->rules[$rule->id]);
    }

    /** @return Collection<int, RoutingRule> */
    private function scoped(string $workspaceId): Collection
    {
        return (new Collection($this->rules))
            ->filter(fn (RoutingRule $rule) => $rule->workspace_id === $workspaceId)
            ->sortBy(fn (RoutingRule $rule) => $rule->position)
            ->values();
    }
}
