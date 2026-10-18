<?php

namespace App\Domains\StatusPage\Services;

use App\Domains\StatusPage\Contracts\StatusIncidentRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusIncidentUpdateRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusMaintenanceRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusProbeRepositoryContract;
use App\Domains\StatusPage\DTOs\PublicStatusOverview;

/** Compose la page publique (section 2.11) : sondes, incidents en cours, maintenances à venir — tout en lecture. */
final class PublicStatusPageService
{
    public function __construct(
        private readonly StatusProbeRepositoryContract $probes,
        private readonly StatusProbeService $probeStats,
        private readonly StatusIncidentRepositoryContract $incidents,
        private readonly StatusIncidentUpdateRepositoryContract $updates,
        private readonly StatusMaintenanceRepositoryContract $maintenances,
    ) {}

    public function forWorkspace(string $workspaceId): PublicStatusOverview
    {
        $probes = $this->probes->forWorkspace($workspaceId)
            ->map(fn ($probe) => [
                'id' => $probe->id,
                'name' => $probe->name,
                'status' => $probe->status,
                'uptime_ratio' => $this->probeStats->uptimeRatio($probe->id),
            ])
            ->all();

        $ongoingIncidents = $this->incidents->ongoingForWorkspace($workspaceId)
            ->map(fn ($incident) => ['incident' => $incident, 'updates' => $this->updates->forIncident($incident->id)])
            ->all();

        return new PublicStatusOverview(
            probes: $probes,
            ongoingIncidents: $ongoingIncidents,
            upcomingMaintenances: $this->maintenances->upcomingForWorkspace($workspaceId)->all(),
        );
    }
}
