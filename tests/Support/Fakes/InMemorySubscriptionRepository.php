<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemorySubscriptionRepository implements SubscriptionRepositoryContract
{
    /** @var array<string, Subscription> */
    private array $subscriptions = [];

    public function __construct(Subscription ...$subscriptions)
    {
        foreach ($subscriptions as $subscription) {
            $this->subscriptions[$subscription->id] = $subscription;
        }
    }

    public function findForWorkspace(string $workspaceId): ?Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->workspace_id === $workspaceId) {
                return $subscription;
            }
        }

        return null;
    }

    public function findForWorkspaceOrFail(string $workspaceId): Subscription
    {
        return $this->findForWorkspace($workspaceId) ?? throw new ModelNotFoundException;
    }

    public function findByProviderSubscriptionIdOrFail(string $providerSubscriptionId): Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->payment_provider_subscription_id === $providerSubscriptionId) {
                return $subscription;
            }
        }

        throw new ModelNotFoundException;
    }

    public function create(array $attributes): Subscription
    {
        $subscription = ModelFactory::subscription($attributes);

        return $this->subscriptions[$subscription->id] = $subscription;
    }

    public function update(Subscription $subscription, array $attributes): Subscription
    {
        $subscription->setRawAttributes($attributes + $subscription->getAttributes(), true);

        return $subscription;
    }

    /** @return Collection<int, Subscription> */
    public function dueForRenewal(): Collection
    {
        return (new Collection($this->subscriptions))
            ->filter(fn (Subscription $subscription) => $subscription->current_period_end !== null && $subscription->current_period_end->isPast())
            ->values();
    }
}
