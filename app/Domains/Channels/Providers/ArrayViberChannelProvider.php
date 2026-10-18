<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\ViberChannelProviderContract;

/** Conserve les envois en mémoire : pilote de la suite de tests, aucun appel réseau n'est jamais fait. */
final class ArrayViberChannelProvider implements ViberChannelProviderContract
{
    /** @var list<array{to: string, body: string, credentials: array<string, mixed>}> */
    private array $sent = [];

    public function sendReply(string $toUserId, string $body, array $credentials): void
    {
        $this->sent[] = ['to' => $toUserId, 'body' => $body, 'credentials' => $credentials];
    }

    /** @return list<array{to: string, body: string, credentials: array<string, mixed>}> */
    public function sent(): array
    {
        return $this->sent;
    }
}
