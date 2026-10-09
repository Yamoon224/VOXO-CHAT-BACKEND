<?php

namespace App\Domains\Billing\Contracts;

use App\Models\Subscription;

/**
 * Grand livre des crédits IA, interne à `Billing` : le solde d'un espace est
 * toujours la somme de ses lignes, jamais un compteur séparé qui pourrait
 * diverger (section 2.13). Un contrat, comme les dépôts du domaine, pour que
 * `QuotaGuardService` et `SubscriptionService` restent testables sans base.
 */
interface AiCreditLedgerContract
{
    public function grant(Subscription $subscription, int $amount, string $reason): void;

    public function topup(Subscription $subscription, int $amount, string $reason): void;

    public function usage(Subscription $subscription, string $reason, ?string $referenceId = null): void;

    public function balance(string $workspaceId): int;
}
