<?php

namespace App\Domains\StatusPage\Http\Resources;

use App\Models\StatusProbe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StatusProbe */
class StatusProbeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'check_interval_minutes' => $this->check_interval_minutes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'last_checked_at' => $this->last_checked_at,
            'consecutive_failures' => $this->consecutive_failures,
        ];
    }
}
