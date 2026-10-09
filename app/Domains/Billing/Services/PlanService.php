<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Models\Plan;
use Illuminate\Support\Collection;

final class PlanService
{
    public function __construct(private readonly PlanRepositoryContract $plans) {}

    /** @return Collection<int, Plan> */
    public function listActive(): Collection
    {
        return $this->plans->listActive();
    }

    public function findBySlug(string $slug): Plan
    {
        return $this->plans->findBySlugOrFail($slug);
    }
}
