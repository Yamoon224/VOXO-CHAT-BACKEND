<?php

namespace App\Domains\Payments\Services;

use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Domains\Payments\Contracts\PaymentGatewayContract;
use App\Domains\Shared\Support\WorkspaceScope;
use RuntimeException;

/**
 * Démarre un passage à un palier payant : crée le client chez le prestataire
 * au besoin, puis ouvre une session de paiement hébergée. L'abonnement n'est
 * activé qu'au retour du rappel de paiement (`SubscriptionWebhookContract`),
 * jamais ici — la session peut encore échouer ou être abandonnée.
 */
final class CheckoutService
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly PlanRepositoryContract $plans,
        private readonly SubscriptionRepositoryContract $subscriptions,
        private readonly string $frontendUrl,
    ) {}

    public function startCheckout(WorkspaceScope $scope, string $planSlug, string $userEmail, string $userName): string
    {
        $plan = $this->plans->findPurchasableBySlugOrFail($planSlug);

        if ($plan->provider_price_id === null) {
            throw new RuntimeException("Le palier « {$plan->slug} » n'a pas de tarif configuré chez le prestataire de paiement.");
        }

        $subscription = $this->subscriptions->findForWorkspaceOrFail($scope->workspaceId);
        $customerId = $subscription->payment_provider_customer_id;

        if ($customerId === null) {
            $customerId = $this->gateway->createCustomer($userEmail, $userName);
            $this->subscriptions->update($subscription, [
                'payment_provider' => 'stripe',
                'payment_provider_customer_id' => $customerId,
            ]);
        }

        return $this->gateway->createCheckoutSession(
            $customerId,
            $plan->provider_price_id,
            ['workspace_id' => $scope->workspaceId, 'plan_slug' => $plan->slug],
            "{$this->frontendUrl}/settings/billing?checkout=success",
            "{$this->frontendUrl}/settings/billing?checkout=canceled",
        );
    }
}
