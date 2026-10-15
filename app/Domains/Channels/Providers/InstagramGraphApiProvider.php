<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\InstagramChannelProviderContract;
use Illuminate\Support\Facades\Http;

/** Pilote par défaut : même Send API unifiée que Messenger, pour la page liée au compte Instagram professionnel. */
final class InstagramGraphApiProvider implements InstagramChannelProviderContract
{
    public function sendReply(string $toIgsid, string $body, array $credentials): void
    {
        Http::post('https://graph.facebook.com/v19.0/me/messages', [
            'recipient' => ['id' => $toIgsid],
            'message' => ['text' => $body],
            'access_token' => (string) ($credentials['page_access_token'] ?? ''),
        ])->throw();
    }
}
