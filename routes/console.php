<?php

use App\Domains\Billing\Console\RenewDueSubscriptionsCommand;
use App\Domains\Billing\Console\SendQuotaAlertsCommand;
use App\Domains\Knowledge\Console\RecrawlDueKnowledgeSourcesCommand;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
|
| L'expiration des invitations arrivera avec le lot qui en a besoin.
|
*/

Schedule::command(RecrawlDueKnowledgeSourcesCommand::class)->hourly();
Schedule::command(RenewDueSubscriptionsCommand::class)->hourly();
Schedule::command(SendQuotaAlertsCommand::class)->hourly();
