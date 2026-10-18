<?php

namespace Database\Factories;

use App\Domains\Campaigns\Enums\CampaignChannel;
use App\Models\CampaignTemplate;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CampaignTemplate> */
class CampaignTemplateFactory extends Factory
{
    protected $model = CampaignTemplate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'channel' => CampaignChannel::Email,
            'subject' => 'Bonjour {{name}}',
            'body' => 'Bonjour {{name}}, ceci est un message pour {{email}}.',
        ];
    }
}
