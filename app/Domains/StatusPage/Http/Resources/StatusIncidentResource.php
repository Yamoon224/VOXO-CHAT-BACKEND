<?php

namespace App\Domains\StatusPage\Http\Resources;

use App\Models\StatusIncident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StatusIncident */
class StatusIncidentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'impact' => $this->impact->value,
            'impact_label' => $this->impact->label(),
            'started_at' => $this->started_at,
            'resolved_at' => $this->resolved_at,
        ];
    }
}
