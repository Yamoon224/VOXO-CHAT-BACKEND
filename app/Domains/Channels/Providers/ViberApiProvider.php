<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\ViberChannelProviderContract;
use Illuminate\Support\Facades\Http;

/** Pilote par défaut : API REST Viber (`send_message`). */
final class ViberApiProvider implements ViberChannelProviderContract
{
    public function sendReply(string $toUserId, string $body, array $credentials): void
    {
        Http::withHeaders(['X-Viber-Auth-Token' => (string) ($credentials['auth_token'] ?? '')])
            ->post('https://chatapi.viber.com/pa/send_message', [
                'receiver' => $toUserId,
                'min_api_version' => 1,
                'sender' => ['name' => 'VOXO'],
                'type' => 'text',
                'text' => $body,
            ])
            ->throw();
    }
}
