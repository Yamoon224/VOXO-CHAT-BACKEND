<?php

namespace App\Domains\Billing\Contracts;

/**
 * « Puis-je ? » — exposé à tous les domaines qui consomment un quota
 * (section 4.4 du cahier des charges : les domaines demandent sans connaître
 * les plans). Les quotas sont vérifiés et décomptés côté serveur
 * (section 2.13) ; ce contrat est le seul point de passage.
 */
interface QuotaGuardContract
{
    public function canAddSeat(string $workspaceId): bool;

    public function canIndexDocument(string $workspaceId): bool;

    public function canConsumeAiCredit(string $workspaceId): bool;

    public function consumeAiCredit(string $workspaceId, string $reason, ?string $referenceId = null): void;
}
