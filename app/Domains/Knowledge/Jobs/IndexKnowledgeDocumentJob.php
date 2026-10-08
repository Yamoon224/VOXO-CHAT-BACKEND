<?php

namespace App\Domains\Knowledge\Jobs;

use App\Domains\Knowledge\Services\KnowledgeIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class IndexKnowledgeDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $documentId) {}

    public function handle(KnowledgeIngestionService $ingestion): void
    {
        $ingestion->index($this->documentId);
    }
}
