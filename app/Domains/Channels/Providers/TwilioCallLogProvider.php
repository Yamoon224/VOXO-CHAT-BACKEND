<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\TelephonyChannelProviderContract;
use Illuminate\Support\Facades\Http;

/**
 * Pilote par défaut : forme Twilio-like (même prestataire que `TwilioSmsProvider`).
 * « Répondre » à un appel envoie un SMS de rappel au numéro appelant — ce
 * canal n'a pas de téléphonie temps réel (voix/IVR), voir
 * `TelephonyChannelProviderContract`.
 */
final class TwilioCallLogProvider implements TelephonyChannelProviderContract
{
    public function sendReply(string $toPhone, string $body, array $credentials): void
    {
        $accountSid = (string) ($credentials['account_sid'] ?? '');

        Http::asForm()
            ->withBasicAuth($accountSid, (string) ($credentials['auth_token'] ?? ''))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'To' => $toPhone,
                'From' => (string) ($credentials['caller_number'] ?? ''),
                'Body' => $body,
            ])
            ->throw();
    }
}
