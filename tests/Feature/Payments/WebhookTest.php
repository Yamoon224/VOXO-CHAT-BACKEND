<?php

namespace Tests\Feature\Payments;

use App\Domains\Billing\Enums\SubscriptionStatus;
use App\Domains\Payments\DTOs\PaymentWebhookEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_session_de_paiement_terminee_active_le_palier_souscrit(): void
    {
        $workspace = Workspace::factory()->create();
        $plan = Plan::where('slug', 'business')->firstOrFail();
        Subscription::factory()->create(['workspace_id' => $workspace->id]);

        $this->paymentGateway()->respondToWebhookWith(new PaymentWebhookEvent(
            type: 'checkout_completed',
            workspaceId: $workspace->id,
            planSlug: $plan->slug,
            providerCustomerId: 'cus_123',
            providerSubscriptionId: 'sub_123',
            currentPeriodEnd: Carbon::parse('2027-01-01'),
        ));

        $this->postJson('/api/v1/public/payments/webhook', [], ['Stripe-Signature' => 'valid'])
            ->assertNoContent();

        $subscription = $workspace->subscription()->first();
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame('sub_123', $subscription->payment_provider_subscription_id);
    }

    #[Test]
    public function une_signature_invalide_est_refusee(): void
    {
        $this->postJson('/api/v1/public/payments/webhook', [], ['Stripe-Signature' => 'invalid'])
            ->assertStatus(400)
            ->assertJsonPath('error_code', 'webhook_signature_invalid');
    }
}
