<?php

namespace App\Domains\Channels\Contracts;

/**
 * Canal « journal d'appels » (lot 7, section 2.5, P3) : pas de téléphonie
 * temps réel (voix/IVR), juste la trace d'un appel. `sendReply()` envoie un
 * SMS de rappel au numéro appelant via le même prestataire — répondre à un
 * appel n'a pas d'autre sens ici.
 */
interface TelephonyChannelProviderContract
{
    /** @param array<string, mixed> $credentials */
    public function sendReply(string $toPhone, string $body, array $credentials): void;
}
