<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Contracts\AiCreditLedgerContract;
use App\Domains\Billing\Contracts\QuotaGuardContract;
use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;

/**
 * « Puis-je ? » : implémente `QuotaGuardContract`. Un espace sans abonnement
 * connu n'est jamais bloqué — un état incohérent ne doit pas se traduire par
 * un blocage silencieux, voir `App\Domains\Shared`.
 */
final class QuotaGuardService implements QuotaGuardContract
{
    public function __construct(
        private readonly SubscriptionRepositoryContract $subscriptions,
        private readonly AiCreditLedgerContract $ledger,
        private readonly MembershipRepositoryContract $memberships,
        private readonly KnowledgeDocumentRepositoryContract $documents,
    ) {}

    public function canAddSeat(string $workspaceId): bool
    {
        $subscription = $this->subscriptions->findForWorkspace($workspaceId);
        $max = $subscription?->plan->max_seats;

        if ($subscription === null || $max === null) {
            return true;
        }

        return $this->memberships->paginate($workspaceId, [], 1)->total() < $max;
    }

    public function canIndexDocument(string $workspaceId): bool
    {
        $subscription = $this->subscriptions->findForWorkspace($workspaceId);
        $max = $subscription?->plan->max_knowledge_documents;

        if ($subscription === null || $max === null) {
            return true;
        }

        return $this->documents->paginate($workspaceId, [], 1)->total() < $max;
    }

    public function canConsumeAiCredit(string $workspaceId): bool
    {
        $subscription = $this->subscriptions->findForWorkspace($workspaceId);

        if ($subscription === null || $subscription->plan->ai_credits_per_month === null) {
            return true;
        }

        return $this->ledger->balance($workspaceId) > 0;
    }

    public function consumeAiCredit(string $workspaceId, string $reason, ?string $referenceId = null): void
    {
        $subscription = $this->subscriptions->findForWorkspace($workspaceId);

        if ($subscription === null) {
            return;
        }

        $this->ledger->usage($subscription, $reason, $referenceId);
    }
}
