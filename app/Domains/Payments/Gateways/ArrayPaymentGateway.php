<?php

namespace App\Domains\Payments\Gateways;

use App\Domains\Payments\Contracts\PaymentGatewayContract;
use App\Domains\Payments\DTOs\PaymentWebhookEvent;
use App\Domains\Payments\Exceptions\WebhookSignatureInvalidException;

/**
 * Doublure de test : aucun appel réseau. Le prochain évènement de rappel se
 * prépare avec `respondToWebhookWith()`, et `signature: 'invalid'` simule une
 * signature falsifiée.
 */
final class ArrayPaymentGateway implements PaymentGatewayContract
{
    private int $sequence = 0;

    /** @var list<array{customer_id: string, provider_price_id: string, metadata: array<string, string>}> */
    private array $checkoutSessions = [];

    /** @var list<string> */
    private array $canceledSubscriptionIds = [];

    private ?PaymentWebhookEvent $nextWebhookEvent = null;

    public function createCustomer(string $email, string $name): string
    {
        return 'cus_test_'.(++$this->sequence);
    }

    public function createCheckoutSession(string $customerId, string $providerPriceId, array $metadata, string $successUrl, string $cancelUrl): string
    {
        $this->checkoutSessions[] = ['customer_id' => $customerId, 'provider_price_id' => $providerPriceId, 'metadata' => $metadata];

        return 'https://checkout.stripe.test/session_'.count($this->checkoutSessions);
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        $this->canceledSubscriptionIds[] = $providerSubscriptionId;
    }

    public function respondToWebhookWith(PaymentWebhookEvent $event): void
    {
        $this->nextWebhookEvent = $event;
    }

    public function parseWebhookEvent(string $rawBody, ?string $signature): PaymentWebhookEvent
    {
        if ($signature === 'invalid') {
            throw WebhookSignatureInvalidException::make();
        }

        return $this->nextWebhookEvent ?? new PaymentWebhookEvent(type: 'unknown');
    }

    /** @return list<array{customer_id: string, provider_price_id: string, metadata: array<string, string>}> */
    public function checkoutSessions(): array
    {
        return $this->checkoutSessions;
    }

    /** @return list<string> */
    public function canceledSubscriptionIds(): array
    {
        return $this->canceledSubscriptionIds;
    }
}
