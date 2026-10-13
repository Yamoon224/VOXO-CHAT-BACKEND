<?php

namespace App\Domains\Billing\Console;

use App\Domains\Billing\Services\QuotaAlertService;
use Illuminate\Console\Command;

/** Relances proactives « vous approchez de votre quota », aux paliers 80 % et 100 % (section 2.13). */
final class SendQuotaAlertsCommand extends Command
{
    protected $signature = 'billing:send-quota-alerts';

    protected $description = "Envoie les e-mails d'alerte de quota aux espaces qui approchent ou atteignent un plafond de leur palier.";

    public function handle(QuotaAlertService $alerts): int
    {
        $sent = $alerts->checkAll();

        $this->info("{$sent} alerte(s) de quota envoyée(s).");

        return self::SUCCESS;
    }
}
