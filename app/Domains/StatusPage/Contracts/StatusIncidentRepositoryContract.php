<?php

namespace App\Domains\StatusPage\Contracts;

use App\Models\StatusIncident;
use Illuminate\Support\Collection;

interface StatusIncidentRepositoryContract
{
    /** @return Collection<int, StatusIncident> */
    public function forWorkspace(string $workspaceId): Collection;

    /** @return Collection<int, StatusIncident> Non résolus uniquement, pour la page publique. */
    public function ongoingForWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): StatusIncident;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): StatusIncident;

    /** @param  array<string, mixed>  $attributes */
    public function update(StatusIncident $incident, array $attributes): StatusIncident;

    public function delete(StatusIncident $incident): void;
}
