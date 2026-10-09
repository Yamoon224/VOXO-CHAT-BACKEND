<?php

namespace App\Domains\Payments\DTOs;

use Illuminate\Support\Carbon;

/**
 * Évènement du prestataire de paiement, normalisé : seuls des types
 * primitifs ou partagés, pour que ce DTO puisse être lu sans que
 * `Billing` ait à connaître `Payments` au-delà de ses contrats.
 */
final readonly class PaymentWebhookEvent
{
    public function __construct(
        public string $type,
        public ?string $workspaceId = null,
        public ?string $planSlug = null,
        public ?string $providerCustomerId = null,
        public ?string $providerSubscriptionId = null,
        public ?string $subscriptionStatus = null,
        public ?Carbon $currentPeriodEnd = null,
        public ?string $providerInvoiceId = null,
        public ?int $invoiceAmountCents = null,
        public ?string $invoiceCurrency = null,
        public ?string $invoiceStatus = null,
        public ?string $hostedInvoiceUrl = null,
        public ?Carbon $invoiceIssuedAt = null,
        public ?Carbon $invoicePaidAt = null,
    ) {}
}
