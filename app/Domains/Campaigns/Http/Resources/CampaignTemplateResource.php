<?php

namespace App\Domains\Campaigns\Http\Resources;

use App\Models\CampaignTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CampaignTemplate */
class CampaignTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'subject' => $this->subject,
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }
}
