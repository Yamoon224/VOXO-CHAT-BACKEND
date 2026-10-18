<?php

namespace App\Domains\StatusPage\DTOs;

use App\Domains\StatusPage\Enums\ProbeStatus;
use App\Models\StatusIncident;
use App\Models\StatusIncidentUpdate;
use App\Models\StatusMaintenance;
use Illuminate\Support\Collection;

/** Tout ce dont la page publique a besoin (section 2.11), assemblé par `PublicStatusPageService`. */
final readonly class PublicStatusOverview
{
    /**
     * @param  list<array{id: string, name: string, status: ProbeStatus, uptime_ratio: ?float}>  $probes
     * @param  list<array{incident: StatusIncident, updates: Collection<int, StatusIncidentUpdate>}>  $ongoingIncidents
     * @param  list<StatusMaintenance>  $upcomingMaintenances
     */
    public function __construct(
        public array $probes,
        public array $ongoingIncidents,
        public array $upcomingMaintenances,
    ) {}
}
