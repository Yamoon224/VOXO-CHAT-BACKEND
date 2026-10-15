<?php

namespace App\Domains\Channels\Http\Controllers\Concerns;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Models\ChannelConnection;
use Illuminate\Http\JsonResponse;

/**
 * Garde commune aux webhooks entrants du lot 5 : un canal désactivé (ou
 * jamais configuré) n'accepte aucun message, et un canal activé n'accepte
 * que les requêtes dont la signature du prestataire est valide — sans quoi
 * n'importe qui pourrait usurper un visiteur en postant directement sur
 * l'URL publique avec le numéro/l'identifiant de son choix.
 */
trait AuthorizesInboundWebhook
{
    /**
     * @param  callable(array<string, mixed>): bool  $signatureValid
     * @return ChannelConnection|JsonResponse La connexion si la requête est autorisée, sinon la réponse 403 à renvoyer telle quelle.
     */
    private function authorizeInboundWebhook(
        ChannelConnectionRepositoryContract $connections,
        string $workspace,
        string $channel,
        callable $signatureValid,
    ): ChannelConnection|JsonResponse {
        $connection = $connections->findForWorkspace($workspace, $channel);

        if ($connection === null || ! $connection->enabled) {
            return response()->json(['message' => "Ce canal n'est pas activé."], 403);
        }

        if (! $signatureValid($connection->credentials)) {
            return response()->json(['message' => 'Signature invalide.'], 403);
        }

        return $connection;
    }
}
