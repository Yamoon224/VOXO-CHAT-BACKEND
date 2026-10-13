<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Contracts\AiCreditLedgerContract;
use App\Domains\Billing\Contracts\QuotaAlertRepositoryContract;
use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Domains\Billing\Enums\QuotaType;
use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;
use App\Models\Subscription;

/**
 * Relances proactives « vous approchez de votre quota » (section 2.13). Les
 * blocages eux-mêmes restent dans `QuotaGuardService` ; ce service avertit
 * avant le mur, aux paliers 80 % et 100 % d'usage, une fois par période par
 * palier (voir `QuotaAlertRepositoryContract`).
 */
final class QuotaAlertService
{
    /** @var list<int> */
    private const THRESHOLDS = [80, 100];

    public function __construct(
        private readonly SubscriptionRepositoryContract $subscriptions,
        private readonly AiCreditLedgerContract $ledger,
        private readonly MembershipRepositoryContract $memberships,
        private readonly KnowledgeDocumentRepositoryContract $documents,
        private readonly QuotaAlertRepositoryContract $alerts,
        private readonly TransactionalMailerContract $mailer,
    ) {}

    /** @return int nombre d'alertes envoyées */
    public function checkAll(): int
    {
        $sent = 0;

        foreach ($this->subscriptions->allActive() as $subscription) {
            $sent += $this->checkSubscription($subscription);
        }

        return $sent;
    }

    private function checkSubscription(Subscription $subscription): int
    {
        $plan = $subscription->plan;
        $sent = 0;

        /** @var array<string, float|null> $usages */
        $usages = [
            QuotaType::Seats->value => $plan->max_seats === null ? null : $this->percentUsed(
                $this->memberships->paginate($subscription->workspace_id, [], 1)->total(),
                $plan->max_seats,
            ),
            QuotaType::Documents->value => $plan->max_knowledge_documents === null ? null : $this->percentUsed(
                $this->documents->paginate($subscription->workspace_id, [], 1)->total(),
                $plan->max_knowledge_documents,
            ),
            QuotaType::AiCredits->value => $plan->ai_credits_per_month === null ? null : $this->percentUsed(
                $plan->ai_credits_per_month - $this->ledger->balance($subscription->workspace_id),
                $plan->ai_credits_per_month,
            ),
        ];

        foreach ($usages as $quotaType => $percent) {
            if ($percent === null) {
                continue;
            }

            $sent += $this->alertIfThresholdCrossed($subscription, QuotaType::from($quotaType), $percent);
        }

        return $sent;
    }

    private function alertIfThresholdCrossed(Subscription $subscription, QuotaType $quotaType, float $percentUsed): int
    {
        $periodStart = $subscription->current_period_start ?? $subscription->created_at ?? now();
        $sent = 0;

        foreach (self::THRESHOLDS as $threshold) {
            if ($percentUsed < $threshold) {
                continue;
            }

            if ($this->alerts->wasAlertedSince($subscription->workspace_id, $quotaType, $threshold, $periodStart)) {
                continue;
            }

            $owner = $this->memberships
                ->paginate($subscription->workspace_id, ['role' => WorkspaceRole::Owner->value], 1)
                ->items()[0] ?? null;

            if ($owner === null) {
                continue;
            }

            $this->mailer->sendQuotaAlert(
                $owner->user->email,
                $subscription->workspace->name,
                $quotaType->label(),
                $threshold,
            );
            $this->alerts->recordSent($subscription->workspace_id, $quotaType, $threshold);
            $sent++;
        }

        return $sent;
    }

    private function percentUsed(int $used, int $max): float
    {
        return $max > 0 ? ($used / $max) * 100 : 100.0;
    }
}
