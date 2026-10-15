<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\WhatsAppChannelProviderContract;
use Illuminate\Support\Facades\Http;

/** Pilote par défaut : WhatsApp Cloud API (Meta). */
final class WhatsAppCloudApiProvider implements WhatsAppChannelProviderContract
{
    public function sendReply(string $toPhone, string $body, array $credentials): void
    {
        $phoneNumberId = (string) ($credentials['phone_number_id'] ?? '');

        Http::withToken((string) ($credentials['access_token'] ?? ''))
            ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $toPhone,
                'type' => 'text',
                'text' => ['body' => $body],
            ])
            ->throw();
    }
}
