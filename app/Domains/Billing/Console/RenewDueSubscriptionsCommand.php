<?php

namespace App\Domains\Billing\Console;

use App\Domains\Billing\Services\SubscriptionService;
use Illuminate\Console\Command;

/** Reconduction planifiée des abonnements échus : fin d'essai, renouvellement de période (section 2.13). */
final class RenewDueSubscriptionsCommand extends Command
{
    protected $signature = 'billing:renew-subscriptions';

    protected $description = 'Bascule les essais expirés vers le palier gratuit et reconduit localement les abonnements dont la période est échue.';

    public function handle(SubscriptionService $subscriptions): int
    {
        $renewed = $subscriptions->renewDueSubscriptions();

        $this->info("{$renewed} abonnement(s) reconduit(s).");

        return self::SUCCESS;
    }
}
