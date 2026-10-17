<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Contracts\WebhookDispatcherContract;
use App\Domains\Integrations\Contracts\WebhookSubscriptionRepositoryContract;
use App\Domains\Integrations\Jobs\DeliverWebhookJob;
use App\Models\WebhookSubscription;

/** Implémente `WebhookDispatcherContract` : un événement par abonnement actif qui y souscrit devient une livraison en file. */
final class WebhookDispatchService implements WebhookDispatcherContract
{
    public function __construct(private readonly WebhookSubscriptionRepositoryContract $subscriptions) {}

    public function dispatch(string $workspaceId, string $eventType, array $payload): void
    {
        foreach ($this->subscriptions->enabledForWorkspace($workspaceId) as $subscription) {
            if ($subscription->subscribesTo($eventType)) {
                $this->queue($subscription, $eventType, $payload);
            }
        }
    }

    /** @param  array<string, mixed>  $payload */
    private function queue(WebhookSubscription $subscription, string $eventType, array $payload): void
    {
        DeliverWebhookJob::dispatch($subscription->id, $eventType, $payload);
    }
}
