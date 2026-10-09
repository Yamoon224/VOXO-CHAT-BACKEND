<?php

namespace App\Domains\Billing\Repositories;

use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Domains\Billing\Exceptions\PlanRequiresSalesContactException;
use App\Models\Plan;
use Illuminate\Support\Collection;

final class EloquentPlanRepository implements PlanRepositoryContract
{
    /** @return Collection<int, Plan> */
    public function listActive(): Collection
    {
        return Plan::query()->where('is_active', true)->orderBy('sort_order')->get();
    }

    public function findBySlugOrFail(string $slug): Plan
    {
        return Plan::query()->where('slug', $slug)->firstOrFail();
    }

    public function findOrFailById(string $id): Plan
    {
        return Plan::query()->findOrFail($id);
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
