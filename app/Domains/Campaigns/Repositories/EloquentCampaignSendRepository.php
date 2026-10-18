<?php

namespace App\Domains\Campaigns\Repositories;

use App\Domains\Campaigns\Contracts\CampaignSendRepositoryContract;
use App\Models\CampaignSend;
use Illuminate\Support\Collection;

final class EloquentCampaignSendRepository implements CampaignSendRepositoryContract
{
    public function forCampaign(string $campaignId): Collection
    {
        return CampaignSend::query()->where('campaign_id', $campaignId)->with('contact:id,name,email')->latest('created_at')->get();
    }

    public function findById(string $id): ?CampaignSend
    {
        return CampaignSend::query()->find($id);
    }

    public function create(array $attributes): CampaignSend
    {
        return CampaignSend::query()->create($attributes);
    }

    public function update(CampaignSend $send, array $attributes): CampaignSend
    {
        $send->update($attributes);

        return $send;
    }
}
