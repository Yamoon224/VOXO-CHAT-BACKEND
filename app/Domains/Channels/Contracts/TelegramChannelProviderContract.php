<?php

namespace App\Domains\Channels\Contracts;

/**
 * Envoi sortant Telegram (Bot API), derrière un contrat commun.
 * `$credentials` porte `bot_token` (voir `MessagingChannel::Telegram`).
 */
interface TelegramChannelProviderContract
{
    /** @param  array<string, mixed>  $credentials */
    public function sendReply(string $toChatId, string $body, array $credentials): void;
}
