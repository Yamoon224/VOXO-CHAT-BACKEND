<?php

namespace App\Domains\Campaigns\Contracts;

use App\Models\CampaignSend;
use Illuminate\Support\Collection;

interface CampaignSendRepositoryContract
{
    /** @return Collection<int, CampaignSend> */
    public function forCampaign(string $campaignId): Collection;

    public function findById(string $id): ?CampaignSend;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): CampaignSend;

    /** @param  array<string, mixed>  $attributes */
    public function update(CampaignSend $send, array $attributes): CampaignSend;
}
