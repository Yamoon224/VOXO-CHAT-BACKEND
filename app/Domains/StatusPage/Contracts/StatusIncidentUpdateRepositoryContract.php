<?php

namespace App\Domains\StatusPage\Contracts;

use App\Models\StatusIncidentUpdate;
use Illuminate\Support\Collection;

interface StatusIncidentUpdateRepositoryContract
{
    /** @return Collection<int, StatusIncidentUpdate> Les plus anciennes en premier : l'ordre de lecture d'une chronologie. */
    public function forIncident(string $incidentId): Collection;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): StatusIncidentUpdate;
}
