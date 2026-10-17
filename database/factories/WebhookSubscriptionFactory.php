<?php

namespace Database\Factories;

use App\Models\WebhookSubscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WebhookSubscription> */
class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'url' => 'https://exemple.fr/webhooks/voxo',
            'secret' => Str::random(40),
            'event_types' => ['conversation.created'],
            'enabled' => true,
        ];
    }
}
