<?php

namespace App\Domains\Payments\Gateways;

use App\Domains\Payments\Contracts\PaymentGatewayContract;
use App\Domains\Payments\DTOs\PaymentWebhookEvent;
use App\Domains\Payments\Exceptions\WebhookSignatureInvalidException;
use Illuminate\Support\Carbon;
use Stripe\Event as StripeEvent;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;

final class StripePaymentGateway implements PaymentGatewayContract
{
    private readonly StripeClient $client;

    public function __construct(string $apiKey, private readonly string $webhookSecret)
    {
        $this->client = new StripeClient($apiKey);
    }

    public function createCustomer(string $email, string $name): string
    {
        return $this->client->customers->create(['email' => $email, 'name' => $name])->id;
    }

    public function createCheckoutSession(
        string $customerId,
        string $providerPriceId,
        array $metadata,
        string $successUrl,
        string $cancelUrl,
    ): string {
        $session = $this->client->checkout->sessions->create([
            'customer' => $customerId,
            'mode' => 'subscription',
            'line_items' => [['price' => $providerPriceId, 'quantity' => 1]],
            'metadata' => $metadata,
            'subscription_data' => ['metadata' => $metadata],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return (string) $session->url;
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        $this->client->subscriptions->cancel($providerSubscriptionId);
    }

    public function parseWebhookEvent(string $rawBody, ?string $signature): PaymentWebhookEvent
    {
        try {
            $event = Webhook::constructEvent($rawBody, (string) $signature, $this->webhookSecret);
        } catch (Throwable) {
            throw WebhookSignatureInvalidException::make();
        }

        return $this->normalize($event);
    }

    private function normalize(StripeEvent $event): PaymentWebhookEvent
    {
        $object = $event->data->object;

        // Les champs qui varient selon le type d'évènement se lisent par accès
        // tableau (`StripeObject implements ArrayAccess`) : leur présence dépend
        // du type reçu, le SDK ne les déclare pas comme propriétés typées.
        return match ($event->type) {
            'checkout.session.completed' => new PaymentWebhookEvent(
                type: 'checkout_completed',
                workspaceId: $object['metadata']['workspace_id'] ?? null,
                planSlug: $object['metadata']['plan_slug'] ?? null,
                providerCustomerId: is_string($object['customer'] ?? null) ? $object['customer'] : null,
                providerSubscriptionId: is_string($object['subscription'] ?? null) ? $object['subscription'] : null,
            ),
            'customer.subscription.updated' => new PaymentWebhookEvent(
                type: 'subscription_updated',
                providerSubscriptionId: $object->id,
                subscriptionStatus: $object['status'],
                currentPeriodEnd: Carbon::createFromTimestamp($object['current_period_end']),
            ),
            'customer.subscription.deleted' => new PaymentWebhookEvent(
                type: 'subscription_canceled',
                providerSubscriptionId: $object->id,
            ),
            'invoice.paid', 'invoice.payment_failed' => new PaymentWebhookEvent(
                type: $event->type === 'invoice.paid' ? 'invoice_paid' : 'invoice_payment_failed',
                providerSubscriptionId: is_string($object['subscription'] ?? null) ? $object['subscription'] : null,
                providerInvoiceId: $object->id,
                invoiceAmountCents: $object['amount_due'],
                invoiceCurrency: strtoupper((string) $object['currency']),
                invoiceStatus: $object['status'],
                hostedInvoiceUrl: $object['hosted_invoice_url'],
                invoiceIssuedAt: Carbon::createFromTimestamp($object['created']),
                invoicePaidAt: ($object['status_transitions']['paid_at'] ?? null) !== null
                    ? Carbon::createFromTimestamp($object['status_transitions']['paid_at'])
                    : null,
            ),
            default => new PaymentWebhookEvent(type: 'unknown'),
        };
    }
}
