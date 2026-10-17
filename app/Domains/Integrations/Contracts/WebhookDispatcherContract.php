<?php

namespace App\Domains\Integrations\Contracts;

/**
 * Lecture étroite exposée aux domaines métier (lot 6, section 2.14) : un
 * événement vient de se produire, à relayer vers chaque abonnement webhook
 * actif qui y a souscrit. `$eventType` est la valeur brute de
 * `WebhookEventType` (primitif, jamais l'enum — ce domaine ne doit pas en
 * dépendre au-delà de ce contrat).
 */
interface WebhookDispatcherContract
{
    /** @param  array<string, mixed>  $payload */
    public function dispatch(string $workspaceId, string $eventType, array $payload): void;
}
