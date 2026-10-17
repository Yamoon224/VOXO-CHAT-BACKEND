<?php

namespace App\Domains\Integrations\Contracts;

use App\Models\WebhookSubscription;
use Illuminate\Support\Collection;

interface WebhookSubscriptionRepositoryContract
{
    /** @return Collection<int, WebhookSubscription> */
    public function forWorkspace(string $workspaceId): Collection;

    /** @return Collection<int, WebhookSubscription> Seulement les abonnements actifs, pour la diffusion. */
    public function enabledForWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): WebhookSubscription;

    /** Sans portée d'espace : pour `DeliverWebhookJob`, qui ne connaît que l'identifiant mis en file. */
    public function findById(string $id): ?WebhookSubscription;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): WebhookSubscription;

    /** @param  array<string, mixed>  $attributes */
    public function update(WebhookSubscription $subscription, array $attributes): WebhookSubscription;

    public function delete(WebhookSubscription $subscription): void;
}
