<?php

namespace App\Domains\Campaigns\Http\Resources;

use App\Models\CampaignSend;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CampaignSend */
class CampaignSendResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_id' => $this->contact_id,
            'contact_name' => $this->contact?->name,
            'contact_email' => $this->contact?->email,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'error' => $this->error,
            'sent_at' => $this->sent_at,
        ];
    }
}
