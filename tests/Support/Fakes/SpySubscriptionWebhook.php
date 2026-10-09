<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\SubscriptionWebhookContract;
use Illuminate\Support\Carbon;

/** Doublure de test : enregistre les appels reçus, un par méthode du contrat. */
final class SpySubscriptionWebhook implements SubscriptionWebhookContract
{
    /** @var list<array{workspace_id: string, plan_slug: string, provider_customer_id: string, provider_subscription_id: string, current_period_end: ?Carbon}> */
    public array $activatedFromCheckout = [];

    /** @var list<array{provider_subscription_id: string, status: string, current_period_end: ?Carbon}> */
    public array $updatedFromProviderStatus = [];

    /** @var list<string> */
    public array $canceledFromProvider = [];

    /** @var list<array{provider_subscription_id: string, provider_invoice_id: string, amount_cents: int, currency: string, status: string, hosted_invoice_url: ?string, issued_at: ?Carbon, paid_at: ?Carbon}> */
    public array $recordedInvoiceEvents = [];

    public function activateFromCheckout(
        string $workspaceId,
        string $planSlug,
        string $providerCustomerId,
        string $providerSubscriptionId,
        ?Carbon $currentPeriodEnd,
    ): void {
        $this->activatedFromCheckout[] = [
            'workspace_id' => $workspaceId, 'plan_slug' => $planSlug,
            'provider_customer_id' => $providerCustomerId, 'provider_subscription_id' => $providerSubscriptionId,
            'current_period_end' => $currentPeriodEnd,
        ];
    }

    public function updateFromProviderStatus(string $providerSubscriptionId, string $status, ?Carbon $currentPeriodEnd): void
    {
        $this->updatedFromProviderStatus[] = [
            'provider_subscription_id' => $providerSubscriptionId, 'status' => $status, 'current_period_end' => $currentPeriodEnd,
        ];
    }

    public function cancelFromProvider(string $providerSubscriptionId): void
    {
        $this->canceledFromProvider[] = $providerSubscriptionId;
    }

    public function recordInvoiceEvent(
        string $providerSubscriptionId,
        string $providerInvoiceId,
        int $amountCents,
        string $currency,
        string $status,
        ?string $hostedInvoiceUrl,
        ?Carbon $issuedAt,
        ?Carbon $paidAt,
    ): void {
        $this->recordedInvoiceEvents[] = [
            'provider_subscription_id' => $providerSubscriptionId, 'provider_invoice_id' => $providerInvoiceId,
            'amount_cents' => $amountCents, 'currency' => $currency, 'status' => $status,
            'hosted_invoice_url' => $hostedInvoiceUrl, 'issued_at' => $issuedAt, 'paid_at' => $paidAt,
        ];
    }
}
