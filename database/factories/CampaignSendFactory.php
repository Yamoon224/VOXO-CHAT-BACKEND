<?php

namespace Database\Factories;

use App\Domains\Campaigns\Enums\CampaignSendStatus;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CampaignSend> */
class CampaignSendFactory extends Factory
{
    protected $model = CampaignSend::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $workspace = Workspace::factory()->create();

        return [
            'workspace_id' => $workspace->id,
            'campaign_id' => Campaign::factory()->create(['workspace_id' => $workspace->id])->id,
            'contact_id' => Contact::factory()->create(['workspace_id' => $workspace->id])->id,
            'status' => CampaignSendStatus::Pending,
        ];
    }
}
