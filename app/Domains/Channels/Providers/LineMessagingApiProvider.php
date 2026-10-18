<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\LineChannelProviderContract;
use Illuminate\Support\Facades\Http;

/** Pilote par défaut : LINE Messaging API (`message/push`). */
final class LineMessagingApiProvider implements LineChannelProviderContract
{
    public function sendReply(string $toUserId, string $body, array $credentials): void
    {
        Http::withToken((string) ($credentials['channel_access_token'] ?? ''))
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $toUserId,
                'messages' => [['type' => 'text', 'text' => $body]],
            ])
            ->throw();
    }
}
