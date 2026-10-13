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

    /** @return Collection<int, Plan> */
    public function listAll(): Collection
    {
        return $this->plans->listAll();
    }

    public function findById(string $id): Plan
    {
        return $this->plans->findOrFailById($id);
    }

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Plan
    {
        return $this->plans->create($attributes);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(string $id, array $attributes): Plan
    {
        return $this->plans->update($this->plans->findOrFailById($id), $attributes);
    }

    public function delete(string $id): void
    {
        $this->plans->delete($this->plans->findOrFailById($id));
    }
}
