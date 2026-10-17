<?php

namespace App\Domains\Integrations\Contracts;

use App\Models\WebhookDelivery;
use Illuminate\Support\Collection;

interface WebhookDeliveryRepositoryContract
{
    /** @return Collection<int, WebhookDelivery> Les plus récentes en premier. */
    public function forSubscription(string $subscriptionId, int $limit): Collection;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): WebhookDelivery;

    /** @param  array<string, mixed>  $attributes */
    public function update(WebhookDelivery $delivery, array $attributes): WebhookDelivery;
}
