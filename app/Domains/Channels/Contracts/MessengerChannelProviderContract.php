<?php

namespace App\Domains\Channels\Contracts;

/**
 * Envoi sortant Messenger (Send API de Meta), derrière un contrat commun.
 * `$credentials` porte `page_access_token` (voir `MessagingChannel::Messenger`).
 */
interface MessengerChannelProviderContract
{
    /** @param  array<string, mixed>  $credentials */
    public function sendReply(string $toPsid, string $body, array $credentials): void;
}
