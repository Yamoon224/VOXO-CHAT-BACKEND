<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Domains\Billing\Exceptions\PlanRequiresSalesContactException;
use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemoryPlanRepository implements PlanRepositoryContract
{
    /** @var array<string, Plan> */
    private array $plans = [];

    public function __construct(Plan ...$plans)
    {
        foreach ($plans as $plan) {
            $this->plans[$plan->id] = $plan;
        }
    }

    /** @return Collection<int, Plan> */
    public function listActive(): Collection
    {
        return (new Collection($this->plans))
            ->filter(fn (Plan $plan) => $plan->is_active)
            ->sortBy('sort_order')
            ->values();
    }

    public function findBySlugOrFail(string $slug): Plan
    {
        foreach ($this->plans as $plan) {
            if ($plan->slug === $slug) {
                return $plan;
            }
        }

        throw new ModelNotFoundException;
    }

    public function findOrFailById(string $id): Plan
    {
        return $this->plans[$id] ?? throw new ModelNotFoundException;
    }

    public function findPurchasableBySlugOrFail(string $slug): Plan
    {
        $plan = $this->findBySlugOrFail($slug);

        if ($plan->is_custom) {
            throw PlanRequiresSalesContactException::make();
        }

        return $plan;
    }
}
