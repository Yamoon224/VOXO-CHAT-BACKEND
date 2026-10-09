<?php

namespace App\Domains\Payments\Contracts;

use App\Domains\Payments\DTOs\PaymentWebhookEvent;
use App\Domains\Payments\Exceptions\WebhookSignatureInvalidException;

/**
 * Prestataire de paiement derrière un contrat (section 2.13) : Stripe en
 * premier, un autre prestataire ou un agrégateur local plus tard sans
 * changer les domaines appelants.
 */
interface PaymentGatewayContract
{
    public function createCustomer(string $email, string $name): string;

    /**
     * @param  array<string, string>  $metadata  reporté sur l'évènement de rappel (ex. `workspace_id`, `plan_slug`)
     * @return string l'URL de paiement hébergée vers laquelle rediriger l'appelant
     */
    public function createCheckoutSession(
        string $customerId,
        string $providerPriceId,
        array $metadata,
        string $successUrl,
        string $cancelUrl,
    ): string;

    public function cancelSubscription(string $providerSubscriptionId): void;

    /** @throws WebhookSignatureInvalidException */
    public function parseWebhookEvent(string $rawBody, ?string $signature): PaymentWebhookEvent;
}
