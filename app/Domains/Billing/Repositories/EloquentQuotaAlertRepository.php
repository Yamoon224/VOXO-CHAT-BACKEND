<?php

namespace App\Domains\Billing\Repositories;

use App\Domains\Billing\Contracts\QuotaAlertRepositoryContract;
use App\Domains\Billing\Enums\QuotaType;
use App\Models\QuotaAlert;
use Illuminate\Support\Carbon;

final class EloquentQuotaAlertRepository implements QuotaAlertRepositoryContract
{
    public function wasAlertedSince(string $workspaceId, QuotaType $quotaType, int $threshold, Carbon $since): bool
    {
        return QuotaAlert::query()
            ->where('workspace_id', $workspaceId)
            ->where('quota_type', $quotaType->value)
            ->where('threshold', $threshold)
            ->where('sent_at', '>=', $since)
            ->exists();
    }

    public function recordSent(string $workspaceId, QuotaType $quotaType, int $threshold): void
    {
        QuotaAlert::query()->updateOrCreate(
            ['workspace_id' => $workspaceId, 'quota_type' => $quotaType->value, 'threshold' => $threshold],
            ['sent_at' => now()],
        );
    }
}
