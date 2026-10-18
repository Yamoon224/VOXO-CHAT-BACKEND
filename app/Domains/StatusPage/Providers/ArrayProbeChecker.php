<?php

namespace App\Domains\StatusPage\Providers;

use App\Domains\StatusPage\Contracts\ProbeCheckerContract;
use App\Domains\StatusPage\DTOs\ProbeCheckResult;

/** Doublure sans appel réseau : pilote de la suite de tests. `up` par défaut, forçable par URL via `fail()`. */
final class ArrayProbeChecker implements ProbeCheckerContract
{
    /** @var array<string, bool> */
    private array $forcedResults = [];

    public function check(string $url): ProbeCheckResult
    {
        return new ProbeCheckResult($this->forcedResults[$url] ?? true, 42);
    }

    public function fail(string $url): void
    {
        $this->forcedResults[$url] = false;
    }

    public function succeed(string $url): void
    {
        $this->forcedResults[$url] = true;
    }
}
