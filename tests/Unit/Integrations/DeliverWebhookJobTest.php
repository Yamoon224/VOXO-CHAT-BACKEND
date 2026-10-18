<?php

namespace Tests\Unit\Integrations;

use App\Domains\Integrations\Jobs\DeliverWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Le job retente 3 fois avec un backoff (voir sa doc) : il doit donc
 * continuer à lever l'exception après l'échec pour que le worker retente —
 * c'est au worker, jamais à la requête qui a déclenché l'évènement, d'en
 * absorber la défaillance.
 */
class DeliverWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_livraison_en_echec_est_tracee_puis_l_exception_est_relevee_pour_la_tentative_suivante(): void
    {
        Http::fake(fn () => Http::response('erreur', 500));
        $subscription = WebhookSubscription::factory()->create(['event_types' => ['conversation.created']]);

        $job = new DeliverWebhookJob($subscription->id, 'conversation.created', ['id' => 'conv-1']);

        $this->expectException(RequestException::class);

        try {
            app()->call([$job, 'handle']);
        } finally {
            $delivery = WebhookDelivery::query()->where('subscription_id', $subscription->id)->first();
            $this->assertNotNull($delivery);
            $this->assertSame('failed', $delivery->status->value);
            $this->assertSame(500, $delivery->response_status);
        }
    }

    #[Test]
    public function une_livraison_reussie_est_tracee_avec_la_signature_attendue(): void
    {
        Http::fake();
        $subscription = WebhookSubscription::factory()->create(['event_types' => ['conversation.created']]);

        $job = new DeliverWebhookJob($subscription->id, 'conversation.created', ['id' => 'conv-1']);
        app()->call([$job, 'handle']);

        $delivery = WebhookDelivery::query()->where('subscription_id', $subscription->id)->first();
        $this->assertSame('success', $delivery->status->value);

        Http::assertSent(function ($request) use ($subscription) {
            $expected = 'sha256='.hash_hmac('sha256', (string) json_encode(['event' => 'conversation.created', 'data' => ['id' => 'conv-1']]), $subscription->secret);

            return $request->header('X-Voxo-Signature')[0] === $expected;
        });
    }

    #[Test]
    public function un_abonnement_desactive_n_est_pas_livre(): void
    {
        Http::fake();
        $subscription = WebhookSubscription::factory()->create(['enabled' => false]);

        $job = new DeliverWebhookJob($subscription->id, 'conversation.created', ['id' => 'conv-1']);
        app()->call([$job, 'handle']);

        Http::assertNothingSent();
        $this->assertSame(0, WebhookDelivery::query()->where('subscription_id', $subscription->id)->count());
    }
}
