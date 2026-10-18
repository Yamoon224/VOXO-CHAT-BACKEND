<?php

namespace Database\Factories;

use App\Domains\Campaigns\Enums\CampaignChannel;
use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\ContactSegment;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $workspace = Workspace::factory()->create();

        return [
            'workspace_id' => $workspace->id,
            'name' => $this->faker->unique()->words(3, true),
            'channel' => CampaignChannel::Email,
            'segment_id' => ContactSegment::factory()->create(['workspace_id' => $workspace->id])->id,
            'template_id' => CampaignTemplate::factory()->create(['workspace_id' => $workspace->id])->id,
            'status' => CampaignStatus::Draft,
        ];
    }
}
