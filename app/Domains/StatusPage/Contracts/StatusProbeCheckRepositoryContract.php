<?php

namespace App\Domains\StatusPage\Contracts;

use App\Models\StatusProbeCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface StatusProbeCheckRepositoryContract
{
    /** @return Collection<int, StatusProbeCheck> Les plus récentes en premier. */
    public function forProbe(string $probeId, int $limit): Collection;

    /** Part de vérifications réussies sur la période, de 0 à 1 ; `null` sans aucune vérification. */
    public function uptimeRatio(string $probeId, Carbon $since): ?float;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): StatusProbeCheck;
}
