<?php

namespace App\Domains\Billing\Contracts;

use App\Models\Subscription;
use Illuminate\Support\Collection;

interface SubscriptionRepositoryContract
{
    public function findForWorkspace(string $workspaceId): ?Subscription;

    public function findForWorkspaceOrFail(string $workspaceId): Subscription;

    public function findByProviderSubscriptionIdOrFail(string $providerSubscriptionId): Subscription;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Subscription;

    /** @param  array<string, mixed>  $attributes */
    public function update(Subscription $subscription, array $attributes): Subscription;

    /**
     * Abonnements dont la période en cours est échue : sondés par la tâche planifiée.
     *
     * @return Collection<int, Subscription>
     */
    public function dueForRenewal(): Collection;
}
