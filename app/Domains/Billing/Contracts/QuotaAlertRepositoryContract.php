<?php

namespace App\Domains\Billing\Contracts;

use App\Domains\Billing\Enums\QuotaType;
use Illuminate\Support\Carbon;

/** Déduplication des alertes de quota, interne à `Billing` (voir `QuotaAlertService`). */
interface QuotaAlertRepositoryContract
{
    public function wasAlertedSince(string $workspaceId, QuotaType $quotaType, int $threshold, Carbon $since): bool;

    public function recordSent(string $workspaceId, QuotaType $quotaType, int $threshold): void;
}
