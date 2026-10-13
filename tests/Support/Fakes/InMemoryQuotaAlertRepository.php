<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\QuotaAlertRepositoryContract;
use App\Domains\Billing\Enums\QuotaType;
use Illuminate\Support\Carbon;

final class InMemoryQuotaAlertRepository implements QuotaAlertRepositoryContract
{
    /** @var array<string, Carbon> */
    private array $sentAt = [];

    public function wasAlertedSince(string $workspaceId, QuotaType $quotaType, int $threshold, Carbon $since): bool
    {
        $sentAt = $this->sentAt[$this->key($workspaceId, $quotaType, $threshold)] ?? null;

        return $sentAt !== null && $sentAt->greaterThanOrEqualTo($since);
    }

    public function recordSent(string $workspaceId, QuotaType $quotaType, int $threshold): void
    {
        $this->sentAt[$this->key($workspaceId, $quotaType, $threshold)] = now();
    }

    private function key(string $workspaceId, QuotaType $quotaType, int $threshold): string
    {
        return "{$workspaceId}:{$quotaType->value}:{$threshold}";
    }
}
