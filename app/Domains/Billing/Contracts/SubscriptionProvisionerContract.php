<?php

namespace App\Domains\Billing\Contracts;

/**
 * Lecture étroite exposée au domaine `Workspaces` : ouvrir un espace ouvre
 * aussi son essai gratuit, sans que l'inscription ait à connaître les plans.
 */
interface SubscriptionProvisionerContract
{
    public function startTrial(string $workspaceId): void;
}
