<?php

namespace App\Domains\Billing\Contracts;

use App\Domains\Billing\Exceptions\PlanRequiresSalesContactException;
use App\Models\Plan;
use Illuminate\Support\Collection;

interface PlanRepositoryContract
{
    /**
     * Paliers actifs, du moins cher au plus cher.
     *
     * @return Collection<int, Plan>
     */
    public function listActive(): Collection;

    public function findBySlugOrFail(string $slug): Plan;

    public function findOrFailById(string $id): Plan;

    /**
     * Le palier désigné, à condition qu'il se souscrive en self-service.
     *
     * Expose à `Payments` la règle métier de `Billing` (l'offre sur devis ne
     * se paie pas par carte) sans que ce domaine ait à connaître
     * `PlanRequiresSalesContactException`, qui n'est pas dans ses `Contracts`.
     *
     * @throws PlanRequiresSalesContactException
     */
    public function findPurchasableBySlugOrFail(string $slug): Plan;
}
