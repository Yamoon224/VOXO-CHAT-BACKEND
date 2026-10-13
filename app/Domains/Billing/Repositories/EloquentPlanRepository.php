<?php

namespace App\Domains\Billing\Repositories;

use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Domains\Billing\Exceptions\PlanInUseException;
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

    /** @return Collection<int, Plan> */
    public function listAll(): Collection
    {
        return Plan::query()->orderBy('sort_order')->get();
    }

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Plan
    {
        return Plan::query()->create($attributes);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(Plan $plan, array $attributes): Plan
    {
        $plan->update($attributes);

        return $plan;
    }

    public function delete(Plan $plan): void
    {
        if ($plan->subscriptions()->exists()) {
            throw PlanInUseException::make();
        }

        $plan->delete();
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
