<?php

namespace Tests\Support\Fakes;

use App\Domains\Integrations\Contracts\WebhookDispatcherContract;

/** Doublure de test : enregistre chaque évènement relayé, sans jamais appeler de webhook réel. */
final class SpyWebhookDispatcher implements WebhookDispatcherContract
{
    /** @var list<array{workspaceId: string, eventType: string, payload: array<string, mixed>}> */
    private array $calls = [];

    /** @param  array<string, mixed>  $payload */
    public function dispatch(string $workspaceId, string $eventType, array $payload): void
    {
        $this->calls[] = ['workspaceId' => $workspaceId, 'eventType' => $eventType, 'payload' => $payload];
    }

    /** @return list<string> */
    public function eventTypes(): array
    {
        return array_column($this->calls, 'eventType');
    }
}
