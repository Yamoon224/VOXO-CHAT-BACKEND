<?php

namespace App\Domains\Channels\Contracts;

/**
 * Envoi sortant Instagram (même Send API unifiée que Messenger, pour la
 * page liée au compte Instagram professionnel), derrière un contrat commun.
 * `$credentials` porte `page_access_token` (voir `MessagingChannel::Instagram`).
 */
interface InstagramChannelProviderContract
{
    /** @param  array<string, mixed>  $credentials */
    public function sendReply(string $toIgsid, string $body, array $credentials): void;
}
