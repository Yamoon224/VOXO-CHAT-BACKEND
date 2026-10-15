<?php

namespace App\Domains\Channels\Contracts;

/**
 * Envoi sortant SMS (forme Twilio-like, prestataire non tranché — section
 * 12 du cahier des charges), derrière un contrat commun. `$credentials`
 * porte `account_sid`, `auth_token` et `from_number` (voir `MessagingChannel::Sms`).
 */
interface SmsChannelProviderContract
{
    /** @param  array<string, mixed>  $credentials */
    public function sendReply(string $toPhone, string $body, array $credentials): void;
}
