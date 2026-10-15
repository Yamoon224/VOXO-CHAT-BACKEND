<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\MessengerChannelProviderContract;
use Illuminate\Support\Facades\Http;

/** Pilote par défaut : Send API de Messenger (Meta). */
final class MessengerGraphApiProvider implements MessengerChannelProviderContract
{
    public function sendReply(string $toPsid, string $body, array $credentials): void
    {
        Http::post('https://graph.facebook.com/v19.0/me/messages', [
            'recipient' => ['id' => $toPsid],
            'message' => ['text' => $body],
            'access_token' => (string) ($credentials['page_access_token'] ?? ''),
        ])->throw();
    }
}
