<?php

namespace App\Domains\Knowledge\Jobs;

use App\Domains\Knowledge\Services\KnowledgeCrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CrawlKnowledgeSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $sourceId) {}

    public function handle(KnowledgeCrawlService $crawler): void
    {
        $crawler->crawl($this->sourceId);
    }
}
