<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Contracts\AiCreditLedgerContract;
use App\Domains\Billing\Contracts\InvoiceRepositoryContract;
use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Domains\Billing\Contracts\SubscriptionProvisionerContract;
use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Domains\Billing\Contracts\SubscriptionWebhookContract;
use App\Domains\Billing\Enums\BillingInterval;
use App\Domains\Billing\Enums\SubscriptionStatus;
use App\Domains\Billing\Exceptions\PlanRequiresSalesContactException;
use App\Domains\Payments\Contracts\PaymentGatewayContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Provisionnement d'essai (`SubscriptionProvisionerContract`), réaction aux
 * évènements du prestataire de paiement (`SubscriptionWebhookContract`), et
 * gestion directe de l'abonnement d'un espace de travail.
 */
final class SubscriptionService implements SubscriptionProvisionerContract, SubscriptionWebhookContract
{
    private const TRIAL_DAYS = 14;

    public function __construct(
        private readonly SubscriptionRepositoryContract $subscriptions,
        private readonly PlanRepositoryContract $plans,
        private readonly InvoiceRepositoryContract $invoices,
        private readonly AiCreditLedgerContract $ledger,
        private readonly PaymentGatewayContract $gateway,
    ) {}

    public function startTrial(string $workspaceId): void
    {
        $trialPlan = $this->bestTrialPlan();

        $subscription = $this->subscriptions->create([
            'workspace_id' => $workspaceId,
            'plan_id' => $trialPlan->id,
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDays(self::TRIAL_DAYS),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(self::TRIAL_DAYS),
        ]);

        if ($trialPlan->ai_credits_per_month !== null) {
            $this->ledger->grant($subscription, $trialPlan->ai_credits_per_month, "Essai gratuit — palier {$trialPlan->name}");
        }
    }

    public function current(WorkspaceScope $scope): Subscription
    {
        return $this->subscriptions->findForWorkspaceOrFail($scope->workspaceId);
    }

    public function aiCreditBalance(string $workspaceId): int
    {
        return $this->ledger->balance($workspaceId);
    }

    /** @throws PlanRequiresSalesContactException */
    public function switchToFreePlan(WorkspaceScope $scope): Subscription
    {
        $subscription = $this->subscriptions->findForWorkspaceOrFail($scope->workspaceId);
        $freePlan = $this->plans->findBySlugOrFail('free');

        if ($subscription->payment_provider_subscription_id !== null) {
            $this->gateway->cancelSubscription($subscription->payment_provider_subscription_id);
        }

        return $this->subscriptions->update($subscription, [
            'plan_id' => $freePlan->id,
            'status' => SubscriptionStatus::Active,
            'payment_provider_subscription_id' => null,
            'canceled_at' => null,
        ]);
    }

    public function cancel(WorkspaceScope $scope): Subscription
    {
        $subscription = $this->subscriptions->findForWorkspaceOrFail($scope->workspaceId);
        $freePlan = $this->plans->findBySlugOrFail('free');

        if ($subscription->payment_provider_subscription_id !== null) {
            $this->gateway->cancelSubscription($subscription->payment_provider_subscription_id);
        }

        return $this->subscriptions->update($subscription, [
            'plan_id' => $freePlan->id,
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'payment_provider_subscription_id' => null,
        ]);
    }

    public function activateFromCheckout(
        string $workspaceId,
        string $planSlug,
        string $providerCustomerId,
        string $providerSubscriptionId,
        ?Carbon $currentPeriodEnd,
    ): void {
        $subscription = $this->subscriptions->findForWorkspaceOrFail($workspaceId);
        $plan = $this->plans->findBySlugOrFail($planSlug);

        $subscription = $this->subscriptions->update($subscription, [
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'payment_provider' => 'stripe',
            'payment_provider_customer_id' => $providerCustomerId,
            'payment_provider_subscription_id' => $providerSubscriptionId,
            'current_period_start' => now(),
            'current_period_end' => $currentPeriodEnd,
            'canceled_at' => null,
        ]);

        if ($plan->ai_credits_per_month !== null) {
            $this->ledger->grant($subscription, $plan->ai_credits_per_month, "Changement de palier — {$plan->name}");
        }
    }

    public function updateFromProviderStatus(string $providerSubscriptionId, string $status, ?Carbon $currentPeriodEnd): void
    {
        $subscription = $this->subscriptions->findByProviderSubscriptionIdOrFail($providerSubscriptionId);

        $mapped = match ($status) {
            'active', 'trialing' => SubscriptionStatus::Active,
            'past_due', 'unpaid', 'incomplete' => SubscriptionStatus::PastDue,
            'canceled' => SubscriptionStatus::Canceled,
            default => $subscription->status,
        };

        $attributes = ['status' => $mapped];

        if ($currentPeriodEnd !== null) {
            $attributes['current_period_end'] = $currentPeriodEnd;
        }

        $this->subscriptions->update($subscription, $attributes);
    }

    public function cancelFromProvider(string $providerSubscriptionId): void
    {
        $subscription = $this->subscriptions->findByProviderSubscriptionIdOrFail($providerSubscriptionId);
        $freePlan = $this->plans->findBySlugOrFail('free');

        $this->subscriptions->update($subscription, [
            'plan_id' => $freePlan->id,
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'payment_provider_subscription_id' => null,
        ]);
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
        $subscription = $this->subscriptions->findByProviderSubscriptionIdOrFail($providerSubscriptionId);

        $attributes = [
            'workspace_id' => $subscription->workspace_id,
            'subscription_id' => $subscription->id,
            'payment_provider_invoice_id' => $providerInvoiceId,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'status' => $status,
            'hosted_invoice_url' => $hostedInvoiceUrl,
            'issued_at' => $issuedAt,
            'paid_at' => $paidAt,
        ];

        $existing = $this->invoices->findByProviderInvoiceId($providerInvoiceId);

        if ($existing !== null) {
            $this->invoices->update($existing, $attributes);
        } else {
            $this->invoices->create($attributes);
        }
    }

    /**
     * Fait avancer les abonnements dont la période est échue : bascule les
     * essais expirés vers le palier gratuit, et reconduit les abonnements
     * payants localement (la vérité du cycle de facturation reste côté
     * prestataire, via ses rappels — ceci ne fait qu'éviter qu'un rappel
     * manqué ne bloque indéfiniment l'octroi des crédits IA).
     */
    public function renewDueSubscriptions(): int
    {
        $renewed = 0;

        foreach ($this->subscriptions->dueForRenewal() as $subscription) {
            if ($subscription->status === SubscriptionStatus::Trialing) {
                $freePlan = $this->plans->findBySlugOrFail('free');

                $subscription = $this->subscriptions->update($subscription, [
                    'plan_id' => $freePlan->id,
                    'status' => SubscriptionStatus::Active,
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                ]);

                if ($freePlan->ai_credits_per_month !== null) {
                    $this->ledger->grant($subscription, $freePlan->ai_credits_per_month, "Fin d'essai — palier gratuit");
                }

                $renewed++;

                continue;
            }

            $plan = $subscription->plan;
            $nextPeriodEnd = $plan->billing_interval === BillingInterval::Year ? now()->addYear() : now()->addMonth();

            $subscription = $this->subscriptions->update($subscription, [
                'current_period_start' => now(),
                'current_period_end' => $nextPeriodEnd,
            ]);

            if ($plan->ai_credits_per_month !== null) {
                $this->ledger->grant($subscription, $plan->ai_credits_per_month, 'Renouvellement de période');
            }

            $renewed++;
        }

        return $renewed;
    }

    private function bestTrialPlan(): Plan
    {
        $plan = $this->plans->listActive()
            ->filter(fn ($plan) => ! $plan->is_custom)
            ->sortByDesc('sort_order')
            ->first();

        if ($plan === null) {
            throw new RuntimeException('Aucun palier actif disponible pour démarrer un essai.');
        }

        return $plan;
    }
}
