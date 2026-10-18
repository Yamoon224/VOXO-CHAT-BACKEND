<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\XChannelProviderContract;
use Illuminate\Support\Facades\Http;

/** Pilote par défaut : API X (Twitter) v2, messages directs. */
final class XApiProvider implements XChannelProviderContract
{
    public function sendReply(string $toUserId, string $body, array $credentials): void
    {
        $accessToken = (string) ($credentials['access_token'] ?? '');

        Http::withToken($accessToken)
            ->post("https://api.twitter.com/2/dm_conversations/with/{$toUserId}/messages", [
                'text' => $body,
            ])
            ->throw();
    }
}
