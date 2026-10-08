<?php

namespace App\Domains\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Sonde de santé consommée par l'orchestrateur et les tests de déploiement.
 *
 * La connectivité à la base est vérifiée explicitement : une API qui répond
 * 200 alors que sa base est injoignable fait basculer le trafic sur une
 * instance incapable de servir une seule conversation.
 */
class HealthController extends Controller
{
    public function __invoke(ConnectionInterface $connection): JsonResponse
    {
        $checks = ['database' => $this->databaseState($connection)];
        $healthy = ! in_array('unreachable', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function databaseState(ConnectionInterface $connection): string
    {
        try {
            $connection->select('select 1');

            return 'ok';
        } catch (Throwable) {
            return 'unreachable';
        }
    }
}
