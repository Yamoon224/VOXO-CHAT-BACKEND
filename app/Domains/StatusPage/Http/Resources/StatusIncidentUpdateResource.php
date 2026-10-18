<?php

namespace App\Domains\StatusPage\Http\Resources;

use App\Models\StatusIncidentUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StatusIncidentUpdate */
class StatusIncidentUpdateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }
}
