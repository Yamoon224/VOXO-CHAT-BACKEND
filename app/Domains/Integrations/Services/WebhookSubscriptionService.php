<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Contracts\WebhookDeliveryRepositoryContract;
use App\Domains\Integrations\Contracts\WebhookSubscriptionRepositoryContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** CRUD des abonnements webhook sortants (section 2.14), diffusés par `WebhookDispatchService`. */
final class WebhookSubscriptionService
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryContract $subscriptions,
        private readonly WebhookDeliveryRepositoryContract $deliveries,
    ) {}

    /** @return Collection<int, WebhookSubscription> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->subscriptions->forWorkspace($scope->workspaceId);
    }

    /** @param  array{url: string, event_types: list<string>, enabled: bool}  $attributes */
    public function create(WorkspaceScope $scope, array $attributes): WebhookSubscription
    {
        return $this->subscriptions->create([
            ...$attributes,
            'workspace_id' => $scope->workspaceId,
            // Généré côté serveur, jamais choisi par l'appelant : la seule
            // garantie que l'abonné peut vérifier `X-Voxo-Signature` repose
            // sur le fait que ce secret n'a jamais transité ailleurs.
            'secret' => Str::random(40),
        ]);
    }

    /** @param  array{url: string, event_types: list<string>, enabled: bool}  $attributes */
    public function update(WorkspaceScope $scope, string $subscriptionId, array $attributes): WebhookSubscription
    {
        $subscription = $this->subscriptions->findInWorkspaceOrFail($scope->workspaceId, $subscriptionId);

        return $this->subscriptions->update($subscription, $attributes);
    }

    public function delete(WorkspaceScope $scope, string $subscriptionId): void
    {
        $this->subscriptions->delete($this->subscriptions->findInWorkspaceOrFail($scope->workspaceId, $subscriptionId));
    }

    /** @return Collection<int, WebhookDelivery> */
    public function deliveries(WorkspaceScope $scope, string $subscriptionId, int $limit = 20): Collection
    {
        $subscription = $this->subscriptions->findInWorkspaceOrFail($scope->workspaceId, $subscriptionId);

        return $this->deliveries->forSubscription($subscription->id, $limit);
    }
}
