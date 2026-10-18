<?php

namespace Tests\Support\Fakes;

use App\Domains\Integrations\Contracts\WebhookDispatcherContract;

/** Doublure de test : ne relaie jamais rien, comme une workspace sans aucun abonnement webhook. */
final class NullWebhookDispatcher implements WebhookDispatcherContract
{
    /** @param  array<string, mixed>  $payload */
    public function dispatch(string $workspaceId, string $eventType, array $payload): void {}
}
