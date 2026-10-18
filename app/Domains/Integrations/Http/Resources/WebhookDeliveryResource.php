<?php

namespace App\Domains\Integrations\Http\Resources;

use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WebhookDelivery */
class WebhookDeliveryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'status' => $this->status->value,
            'response_status' => $this->response_status,
            'attempted_at' => $this->attempted_at,
            'created_at' => $this->created_at,
        ];
    }
}
