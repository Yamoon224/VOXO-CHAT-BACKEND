<?php

use App\Domains\Billing\Console\RenewDueSubscriptionsCommand;
use App\Domains\Billing\Console\SendQuotaAlertsCommand;
use App\Domains\Knowledge\Console\RecrawlDueKnowledgeSourcesCommand;
use App\Domains\StatusPage\Console\CheckStatusProbesCommand;
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
// Chaque minute, pas horaire : `check_interval_minutes` descend à 5 par
// défaut (section 2.11), une sonde due ne doit pas attendre une heure.
Schedule::command(CheckStatusProbesCommand::class)->everyMinute();
