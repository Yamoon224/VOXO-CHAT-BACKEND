<?php

namespace App\Domains\Billing\Contracts;

use Illuminate\Support\Carbon;

/**
 * Lecture étroite exposée au domaine `Payments` : un évènement du prestataire
 * met à jour un abonnement, sans que ce domaine connaisse les plans ni les
 * crédits IA. Chaque méthode ne prend que des types primitifs ou partagés
 * (jamais un DTO de `Payments`), pour que cette interface reste la propriété
 * de `Billing`.
 */
interface SubscriptionWebhookContract
{
    /** La session de paiement d'un changement de palier s'est terminée avec succès. */
    public function activateFromCheckout(
        string $workspaceId,
        string $planSlug,
        string $providerCustomerId,
        string $providerSubscriptionId,
        ?Carbon $currentPeriodEnd,
    ): void;

    public function updateFromProviderStatus(string $providerSubscriptionId, string $status, ?Carbon $currentPeriodEnd): void;

    public function cancelFromProvider(string $providerSubscriptionId): void;

    public function recordInvoiceEvent(
        string $providerSubscriptionId,
        string $providerInvoiceId,
        int $amountCents,
        string $currency,
        string $status,
        ?string $hostedInvoiceUrl,
        ?Carbon $issuedAt,
        ?Carbon $paidAt,
    ): void;
}
