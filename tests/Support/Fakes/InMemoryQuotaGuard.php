<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\QuotaGuardContract;

/** Permissif par défaut : un test force `$allowSeats`/`$allowDocuments`/`$allowAiCredits` à `false` pour simuler un palier épuisé. */
final class InMemoryQuotaGuard implements QuotaGuardContract
{
    public bool $allowSeats = true;

    public bool $allowDocuments = true;

    public bool $allowAiCredits = true;

    /** @var list<array{workspace_id: string, reason: string, reference_id: string|null}> */
    public array $consumed = [];

    public function canAddSeat(string $workspaceId): bool
    {
        return $this->allowSeats;
    }

    public function canIndexDocument(string $workspaceId): bool
    {
        return $this->allowDocuments;
    }

    public function canConsumeAiCredit(string $workspaceId): bool
    {
        return $this->allowAiCredits;
    }

    public function consumeAiCredit(string $workspaceId, string $reason, ?string $referenceId = null): void
    {
        $this->consumed[] = ['workspace_id' => $workspaceId, 'reason' => $reason, 'reference_id' => $referenceId];
    }
}
