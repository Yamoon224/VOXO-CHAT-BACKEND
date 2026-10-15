<?php

namespace App\Domains\Channels\Contracts;

/**
 * Envoi sortant WhatsApp Business (Cloud API), derrière un contrat commun —
 * même principe qu'`EmailChannelProviderContract`. `$credentials` porte
 * `access_token` et `phone_number_id` (voir `MessagingChannel::WhatsApp`).
 */
interface WhatsAppChannelProviderContract
{
    /** @param  array<string, mixed>  $credentials */
    public function sendReply(string $toPhone, string $body, array $credentials): void;
}
