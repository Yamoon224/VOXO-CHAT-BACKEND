<?php

namespace Tests\Support\Fakes;

use App\Domains\Channels\Contracts\OutboundChannelDispatcherContract;

/** Doublure de test : enregistre les relais demandés, sans jamais envoyer d'e-mail. */
final class SpyOutboundChannelDispatcher implements OutboundChannelDispatcherContract
{
    /** @var list<array{workspaceId: string, channel: string, recipientEmail: ?string, body: string}> */
    private array $calls = [];

    public function dispatchReply(string $workspaceId, string $channel, ?string $recipientEmail, string $body): void
    {
        $this->calls[] = ['workspaceId' => $workspaceId, 'channel' => $channel, 'recipientEmail' => $recipientEmail, 'body' => $body];
    }

    /** @return list<array{workspaceId: string, channel: string, recipientEmail: ?string, body: string}> */
    public function calls(): array
    {
        return $this->calls;
    }
}
