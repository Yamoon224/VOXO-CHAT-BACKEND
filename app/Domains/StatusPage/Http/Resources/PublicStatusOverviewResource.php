<?php

namespace App\Domains\StatusPage\Http\Resources;

use App\Domains\StatusPage\DTOs\PublicStatusOverview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PublicStatusOverview */
class PublicStatusOverviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'probes' => array_map(fn (array $probe) => [
                'id' => $probe['id'],
                'name' => $probe['name'],
                'status' => $probe['status']->value,
                'status_label' => $probe['status']->label(),
                'uptime_ratio' => $probe['uptime_ratio'],
            ], $this->probes),
            'ongoing_incidents' => array_map(fn (array $row) => [
                ...(new StatusIncidentResource($row['incident']))->toArray($request),
                'updates' => StatusIncidentUpdateResource::collection($row['updates']),
            ], $this->ongoingIncidents),
            'upcoming_maintenances' => StatusMaintenanceResource::collection($this->upcomingMaintenances),
        ];
    }
}
