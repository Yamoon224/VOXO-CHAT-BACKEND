<?php

namespace Tests\Unit\Payments;

use App\Domains\Payments\DTOs\PaymentWebhookEvent;
use App\Domains\Payments\Exceptions\WebhookSignatureInvalidException;
use App\Domains\Payments\Gateways\ArrayPaymentGateway;
use App\Domains\Payments\Services\WebhookProcessor;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\SpySubscriptionWebhook;
use Tests\TestCase;

class WebhookProcessorTest extends TestCase
{
    #[Test]
    public function une_session_de_paiement_terminee_active_l_abonnement(): void
    {
        $gateway = new ArrayPaymentGateway;
        $periodEnd = Carbon::parse('2027-01-01');
        $gateway->respondToWebhookWith(new PaymentWebhookEvent(
            type: 'checkout_completed',
            workspaceId: 'workspace-1',
            planSlug: 'business',
            providerCustomerId: 'cus_123',
            providerSubscriptionId: 'sub_123',
            currentPeriodEnd: $periodEnd,
        ));
        $subscriptions = new SpySubscriptionWebhook;

        (new WebhookProcessor($gateway, $subscriptions))->process('{}', 'valid');

        $this->assertCount(1, $subscriptions->activatedFromCheckout);
        $this->assertSame('workspace-1', $subscriptions->activatedFromCheckout[0]['workspace_id']);
        $this->assertSame('business', $subscriptions->activatedFromCheckout[0]['plan_slug']);
    }

    #[Test]
    public function une_session_de_paiement_incomplete_n_active_rien(): void
    {
        $gateway = new ArrayPaymentGateway;
        $gateway->respondToWebhookWith(new PaymentWebhookEvent(type: 'checkout_completed', workspaceId: 'workspace-1'));
        $subscriptions = new SpySubscriptionWebhook;

        (new WebhookProcessor($gateway, $subscriptions))->process('{}', 'valid');

        $this->assertSame([], $subscriptions->activatedFromCheckout);
    }

    #[Test]
    public function un_changement_de_statut_met_a_jour_l_abonnement(): void
    {
        $gateway = new ArrayPaymentGateway;
        $gateway->respondToWebhookWith(new PaymentWebhookEvent(
            type: 'subscription_updated', providerSubscriptionId: 'sub_123', subscriptionStatus: 'past_due',
        ));
        $subscriptions = new SpySubscriptionWebhook;

        (new WebhookProcessor($gateway, $subscriptions))->process('{}', 'valid');

        $this->assertSame([['provider_subscription_id' => 'sub_123', 'status' => 'past_due', 'current_period_end' => null]], $subscriptions->updatedFromProviderStatus);
    }

    #[Test]
    public function une_resiliation_cote_prestataire_est_repercutee(): void
    {
        $gateway = new ArrayPaymentGateway;
        $gateway->respondToWebhookWith(new PaymentWebhookEvent(type: 'subscription_canceled', providerSubscriptionId: 'sub_123'));
        $subscriptions = new SpySubscriptionWebhook;

        (new WebhookProcessor($gateway, $subscriptions))->process('{}', 'valid');

        $this->assertSame(['sub_123'], $subscriptions->canceledFromProvider);
    }

    #[Test]
    public function un_evenement_de_facture_est_enregistre(): void
    {
        $gateway = new ArrayPaymentGateway;
        $gateway->respondToWebhookWith(new PaymentWebhookEvent(
            type: 'invoice_paid', providerSubscriptionId: 'sub_123', providerInvoiceId: 'in_123',
            invoiceAmountCents: 1900, invoiceCurrency: 'EUR', invoiceStatus: 'paid',
        ));
        $subscriptions = new SpySubscriptionWebhook;

        (new WebhookProcessor($gateway, $subscriptions))->process('{}', 'valid');

        $this->assertCount(1, $subscriptions->recordedInvoiceEvents);
        $this->assertSame('in_123', $subscriptions->recordedInvoiceEvents[0]['provider_invoice_id']);
    }

    #[Test]
    public function une_signature_invalide_est_refusee(): void
    {
        $this->expectException(WebhookSignatureInvalidException::class);

        (new WebhookProcessor(new ArrayPaymentGateway, new SpySubscriptionWebhook))->process('{}', 'invalid');
    }
}
