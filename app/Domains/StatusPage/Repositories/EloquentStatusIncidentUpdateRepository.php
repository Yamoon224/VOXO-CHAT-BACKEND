<?php

namespace App\Domains\StatusPage\Repositories;

use App\Domains\StatusPage\Contracts\StatusIncidentUpdateRepositoryContract;
use App\Models\StatusIncidentUpdate;
use Illuminate\Support\Collection;

final class EloquentStatusIncidentUpdateRepository implements StatusIncidentUpdateRepositoryContract
{
    public function forIncident(string $incidentId): Collection
    {
        return StatusIncidentUpdate::query()->where('incident_id', $incidentId)->oldest('created_at')->get();
    }

    public function create(array $attributes): StatusIncidentUpdate
    {
        return StatusIncidentUpdate::query()->create($attributes);
    }
}
