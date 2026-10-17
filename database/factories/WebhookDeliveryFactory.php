<?php

namespace Database\Factories;

use App\Domains\Integrations\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookDelivery> */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'subscription_id' => WebhookSubscription::factory(),
            'event_type' => 'conversation.created',
            'payload' => ['id' => 'conversation-1'],
            'status' => WebhookDeliveryStatus::Pending,
            'response_status' => null,
            'attempted_at' => null,
        ];
    }
}
