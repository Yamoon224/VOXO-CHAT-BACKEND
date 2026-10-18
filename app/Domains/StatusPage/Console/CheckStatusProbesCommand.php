<?php

namespace App\Domains\StatusPage\Console;

use App\Domains\StatusPage\Contracts\StatusProbeRepositoryContract;
use App\Domains\StatusPage\Jobs\CheckStatusProbeJob;
use Illuminate\Console\Command;

/** Vérification planifiée des sondes de disponibilité (section 2.11). */
final class CheckStatusProbesCommand extends Command
{
    protected $signature = 'status-page:check-probes';

    protected $description = 'Met en file la vérification des sondes dont la cadence est due.';

    public function handle(StatusProbeRepositoryContract $probes): int
    {
        $due = $probes->dueForCheck();

        foreach ($due as $probe) {
            CheckStatusProbeJob::dispatch($probe->id);
        }

        $this->info("{$due->count()} sonde(s) mise(s) en file pour vérification.");

        return self::SUCCESS;
    }
}
