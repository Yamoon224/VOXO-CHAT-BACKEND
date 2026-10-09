<?php

namespace App\Domains\Billing\Repositories;

use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Models\Subscription;
use Illuminate\Support\Collection;

final class EloquentSubscriptionRepository implements SubscriptionRepositoryContract
{
    public function findForWorkspace(string $workspaceId): ?Subscription
    {
        return Subscription::query()->with('plan')->where('workspace_id', $workspaceId)->first();
    }

    public function findForWorkspaceOrFail(string $workspaceId): Subscription
    {
        return Subscription::query()->with('plan')->where('workspace_id', $workspaceId)->firstOrFail();
    }

    public function findByProviderSubscriptionIdOrFail(string $providerSubscriptionId): Subscription
    {
        return Subscription::query()
            ->with('plan')
            ->where('payment_provider_subscription_id', $providerSubscriptionId)
            ->firstOrFail();
    }

    public function create(array $attributes): Subscription
    {
        return Subscription::create($attributes);
    }

    public function update(Subscription $subscription, array $attributes): Subscription
    {
        $subscription->update($attributes);

        // `plan_id` a pu changer : la relation chargée avant la mise à jour
        // resterait sinon sur l'ancien palier en mémoire.
        return $subscription->load('plan');
    }

    /** @return Collection<int, Subscription> */
    public function dueForRenewal(): Collection
    {
        return Subscription::query()
            ->with('plan')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->get();
    }
}
