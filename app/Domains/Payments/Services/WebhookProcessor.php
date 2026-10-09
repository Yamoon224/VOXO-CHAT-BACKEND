<?php

namespace App\Domains\Payments\Services;

use App\Domains\Billing\Contracts\SubscriptionWebhookContract;
use App\Domains\Payments\Contracts\PaymentGatewayContract;
use App\Domains\Payments\DTOs\PaymentWebhookEvent;
use App\Domains\Payments\Exceptions\WebhookSignatureInvalidException;

/** Traduit un rappel brut du prestataire en appel à `SubscriptionWebhookContract`. */
final class WebhookProcessor
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly SubscriptionWebhookContract $subscriptions,
    ) {}

    /** @throws WebhookSignatureInvalidException */
    public function process(string $rawBody, ?string $signature): void
    {
        $event = $this->gateway->parseWebhookEvent($rawBody, $signature);

        match ($event->type) {
            'checkout_completed' => $this->applyCheckoutCompleted($event),
            'subscription_updated' => $this->subscriptions->updateFromProviderStatus(
                (string) $event->providerSubscriptionId,
                (string) $event->subscriptionStatus,
                $event->currentPeriodEnd,
            ),
            'subscription_canceled' => $this->subscriptions->cancelFromProvider((string) $event->providerSubscriptionId),
            'invoice_paid', 'invoice_payment_failed' => $this->subscriptions->recordInvoiceEvent(
                (string) $event->providerSubscriptionId,
                (string) $event->providerInvoiceId,
                (int) $event->invoiceAmountCents,
                (string) $event->invoiceCurrency,
                (string) $event->invoiceStatus,
                $event->hostedInvoiceUrl,
                $event->invoiceIssuedAt,
                $event->invoicePaidAt,
            ),
            default => null,
        };
    }

    private function applyCheckoutCompleted(PaymentWebhookEvent $event): void
    {
        if ($event->workspaceId === null || $event->planSlug === null
            || $event->providerCustomerId === null || $event->providerSubscriptionId === null) {
            return;
        }

        $this->subscriptions->activateFromCheckout(
            $event->workspaceId,
            $event->planSlug,
            $event->providerCustomerId,
            $event->providerSubscriptionId,
            $event->currentPeriodEnd,
        );
    }
}
