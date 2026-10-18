<?php

namespace App\Domains\StatusPage\Jobs;

use App\Domains\StatusPage\Contracts\ProbeCheckerContract;
use App\Domains\StatusPage\Contracts\StatusProbeRepositoryContract;
use App\Domains\StatusPage\Services\StatusProbeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Vérifie une sonde et enregistre le résultat — en file pour qu'une URL lente ne retienne pas la commande planifiée. */
final class CheckStatusProbeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly string $probeId) {}

    public function handle(StatusProbeRepositoryContract $probes, ProbeCheckerContract $checker, StatusProbeService $service): void
    {
        $probe = $probes->findById($this->probeId);

        if ($probe === null) {
            return;
        }

        $service->recordCheck($probe, $checker->check($probe->url));
    }
}
