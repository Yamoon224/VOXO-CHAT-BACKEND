<?php

namespace App\Domains\Billing\Repositories;

use App\Domains\Billing\Contracts\AiCreditLedgerContract;
use App\Domains\Billing\Enums\AiCreditTransactionType;
use App\Models\AiCreditTransaction;
use App\Models\Subscription;

final class EloquentAiCreditLedger implements AiCreditLedgerContract
{
    public function grant(Subscription $subscription, int $amount, string $reason): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->record($subscription, AiCreditTransactionType::Grant, $amount, $reason);
    }

    public function topup(Subscription $subscription, int $amount, string $reason): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->record($subscription, AiCreditTransactionType::Topup, $amount, $reason);
    }

    public function usage(Subscription $subscription, string $reason, ?string $referenceId = null): void
    {
        $this->record($subscription, AiCreditTransactionType::Usage, -1, $reason, $referenceId);
    }

    public function balance(string $workspaceId): int
    {
        return (int) AiCreditTransaction::query()->where('workspace_id', $workspaceId)->sum('amount');
    }

    private function record(Subscription $subscription, AiCreditTransactionType $type, int $amount, string $reason, ?string $referenceId = null): void
    {
        AiCreditTransaction::create([
            'workspace_id' => $subscription->workspace_id,
            'subscription_id' => $subscription->id,
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'reference_id' => $referenceId,
        ]);
    }
}
