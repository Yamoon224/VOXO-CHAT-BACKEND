<?php

namespace App\Domains\Knowledge\Console;

use App\Domains\Knowledge\Contracts\KnowledgeSourceRepositoryContract;
use App\Domains\Knowledge\Jobs\CrawlKnowledgeSourceJob;
use Illuminate\Console\Command;

/** Ré-indexation planifiée des sources de type site web (section 2.1 du cahier des charges). */
final class RecrawlDueKnowledgeSourcesCommand extends Command
{
    protected $signature = 'knowledge:recrawl-due-sources';

    protected $description = 'Met en file la ré-exploration des sources web dont la cadence planifiée est due.';

    public function handle(KnowledgeSourceRepositoryContract $sources): int
    {
        $due = $sources->dueForRecrawl();

        foreach ($due as $source) {
            CrawlKnowledgeSourceJob::dispatch($source->id);
        }

        $this->info("{$due->count()} source(s) mise(s) en file pour ré-exploration.");

        return self::SUCCESS;
    }
}
