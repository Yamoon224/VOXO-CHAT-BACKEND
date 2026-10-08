<?php

use App\Domains\Knowledge\Console\RecrawlDueKnowledgeSourcesCommand;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
|
| L'expiration des invitations et les relances arriveront avec les lots qui
| en ont besoin.
|
*/

Schedule::command(RecrawlDueKnowledgeSourcesCommand::class)->hourly();
