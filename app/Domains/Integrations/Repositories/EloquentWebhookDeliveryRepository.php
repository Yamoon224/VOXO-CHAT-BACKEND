<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Contracts\WebhookDeliveryRepositoryContract;
use App\Models\WebhookDelivery;
use Illuminate\Support\Collection;

final class EloquentWebhookDeliveryRepository implements WebhookDeliveryRepositoryContract
{
    public function forSubscription(string $subscriptionId, int $limit): Collection
    {
        return WebhookDelivery::query()
            ->where('subscription_id', $subscriptionId)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $attributes): WebhookDelivery
    {
        return WebhookDelivery::query()->create($attributes);
    }

    public function update(WebhookDelivery $delivery, array $attributes): WebhookDelivery
    {
        $delivery->update($attributes);

        return $delivery;
    }
}
