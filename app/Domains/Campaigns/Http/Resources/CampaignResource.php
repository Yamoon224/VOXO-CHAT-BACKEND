<?php

namespace App\Domains\Campaigns\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Campaign
 *
 * Les comptes d'envoi (`*_sends_count`) viennent de `EloquentCampaignRepository::withSendCounts()` :
 * toujours chargés, jamais `null`.
 */
class CampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'segment_id' => $this->segment_id,
            'template_id' => $this->template_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
            'stats' => [
                'pending' => $this->pending_sends_count,
                'sent' => $this->sent_sends_count,
                'failed' => $this->failed_sends_count,
                'skipped' => $this->skipped_sends_count,
            ],
        ];
    }
}
