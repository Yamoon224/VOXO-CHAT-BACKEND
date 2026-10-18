<?php

namespace App\Domains\StatusPage\Providers;

use App\Domains\StatusPage\Contracts\ProbeCheckerContract;
use App\Domains\StatusPage\DTOs\ProbeCheckResult;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Vérifie une sonde par une requête HTTP réelle : `up` sur un statut 2xx, `down` sinon ou sur erreur réseau/délai dépassé. */
final class HttpProbeChecker implements ProbeCheckerContract
{
    private const TIMEOUT_SECONDS = 10;

    public function check(string $url): ProbeCheckResult
    {
        $startedAt = microtime(true);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get($url);

            return new ProbeCheckResult($response->successful(), $this->elapsedMs($startedAt));
        } catch (Throwable) {
            return new ProbeCheckResult(false, $this->elapsedMs($startedAt));
        }
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
