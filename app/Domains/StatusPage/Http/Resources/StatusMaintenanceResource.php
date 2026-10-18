<?php

namespace App\Domains\StatusPage\Http\Resources;

use App\Models\StatusMaintenance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StatusMaintenance */
class StatusMaintenanceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'scheduled_start' => $this->scheduled_start,
            'scheduled_end' => $this->scheduled_end,
        ];
    }
}
