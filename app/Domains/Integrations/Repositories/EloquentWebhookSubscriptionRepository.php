<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Contracts\WebhookSubscriptionRepositoryContract;
use App\Models\WebhookSubscription;
use Illuminate\Support\Collection;

final class EloquentWebhookSubscriptionRepository implements WebhookSubscriptionRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return WebhookSubscription::query()
            ->where('workspace_id', $workspaceId)
            ->latest('created_at')
            ->get();
    }

    public function enabledForWorkspace(string $workspaceId): Collection
    {
        return WebhookSubscription::query()
            ->where('workspace_id', $workspaceId)
            ->where('enabled', true)
            ->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): WebhookSubscription
    {
        return WebhookSubscription::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function findById(string $id): ?WebhookSubscription
    {
        return WebhookSubscription::query()->find($id);
    }

    public function create(array $attributes): WebhookSubscription
    {
        return WebhookSubscription::query()->create($attributes);
    }

    public function update(WebhookSubscription $subscription, array $attributes): WebhookSubscription
    {
        $subscription->update($attributes);

        return $subscription;
    }

    public function delete(WebhookSubscription $subscription): void
    {
        $subscription->delete();
    }
}
