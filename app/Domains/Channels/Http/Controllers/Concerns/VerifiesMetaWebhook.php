<?php

namespace App\Domains\Channels\Http\Controllers\Concerns;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Poignée de main GET exigée par Meta (WhatsApp, Messenger, Instagram) avant
 * d'accepter une URL de webhook dans la console de l'app : elle envoie
 * `hub.mode=subscribe`, `hub.verify_token` et `hub.challenge`, et attend
 * `hub.challenge` en texte brut si le jeton correspond à celui de l'espace.
 */
trait VerifiesMetaWebhook
{
    private function verifyMetaWebhook(
        Request $request,
        ChannelConnectionRepositoryContract $connections,
        string $workspace,
        string $channel,
    ): Response {
        $connection = $connections->findOrCreateForWorkspace($workspace, $channel);
        $expected = $connection->credentials['verify_token'] ?? null;

        // Meta envoie `hub.mode`/`hub.verify_token`/`hub.challenge` (avec des
        // points) : PHP transforme les points d'une clé de query string en
        // tirets bas avant de peupler `$_GET`, donc `hub_mode` ici est le bon nom.
        if ($request->query('hub_mode') !== 'subscribe' || $request->query('hub_verify_token') !== $expected) {
            return response('Jeton de vérification invalide.', 403);
        }

        return response((string) $request->query('hub_challenge'), 200);
    }
}
