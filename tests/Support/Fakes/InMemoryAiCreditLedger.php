<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\AiCreditLedgerContract;
use App\Models\Subscription;

final class InMemoryAiCreditLedger implements AiCreditLedgerContract
{
    /** @var array<string, int> */
    private array $balances = [];

    /** @var list<array{workspace_id: string, reason: string, reference_id: string|null}> */
    public array $usages = [];

    public function grant(Subscription $subscription, int $amount, string $reason): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->balances[$subscription->workspace_id] = ($this->balances[$subscription->workspace_id] ?? 0) + $amount;
    }

    public function topup(Subscription $subscription, int $amount, string $reason): void
    {
        $this->grant($subscription, $amount, $reason);
    }

    public function usage(Subscription $subscription, string $reason, ?string $referenceId = null): void
    {
        $this->balances[$subscription->workspace_id] = ($this->balances[$subscription->workspace_id] ?? 0) - 1;
        $this->usages[] = ['workspace_id' => $subscription->workspace_id, 'reason' => $reason, 'reference_id' => $referenceId];
    }

    public function balance(string $workspaceId): int
    {
        return $this->balances[$workspaceId] ?? 0;
    }
}
