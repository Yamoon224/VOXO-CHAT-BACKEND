<?php

namespace App\Domains\Integrations\Jobs;

use App\Domains\Integrations\Contracts\WebhookDeliveryRepositoryContract;
use App\Domains\Integrations\Contracts\WebhookSubscriptionRepositoryContract;
use App\Domains\Integrations\Enums\WebhookDeliveryStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Livre un événement à un abonnement webhook, en file pour ne jamais
 * ralentir la requête qui l'a déclenché — la défaillance d'un abonné
 * (URL morte, lenteur) ne doit jamais rejaillir sur l'espace de travail.
 */
final class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $eventType,
        public readonly array $payload,
    ) {}

    public function handle(WebhookSubscriptionRepositoryContract $subscriptions, WebhookDeliveryRepositoryContract $deliveries): void
    {
        $subscription = $subscriptions->findById($this->subscriptionId);

        if ($subscription === null || ! $subscription->enabled) {
            return;
        }

        $delivery = $deliveries->create([
            'workspace_id' => $subscription->workspace_id,
            'subscription_id' => $subscription->id,
            'event_type' => $this->eventType,
            'payload' => $this->payload,
            'status' => WebhookDeliveryStatus::Pending,
        ]);

        $body = ['event' => $this->eventType, 'data' => $this->payload];
        $signature = 'sha256='.hash_hmac('sha256', (string) json_encode($body), $subscription->secret);

        try {
            $response = Http::withHeaders(['X-Voxo-Signature' => $signature])->post($subscription->url, $body);

            $deliveries->update($delivery, [
                'status' => $response->successful() ? WebhookDeliveryStatus::Success : WebhookDeliveryStatus::Failed,
                'response_status' => $response->status(),
                'attempted_at' => now(),
            ]);

            $response->throw();
        } catch (Throwable $exception) {
            $deliveries->update($delivery, ['status' => WebhookDeliveryStatus::Failed, 'attempted_at' => now()]);

            throw $exception;
        }
    }
}
