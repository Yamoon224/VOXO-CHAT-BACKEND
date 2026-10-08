<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeSourceRepositoryContract;
use App\Domains\Knowledge\Contracts\WebCrawlerContract;
use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Jobs\IndexKnowledgeDocumentJob;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Explore une source de type site web et crée ou met à jour un document par
 * page. Une page déjà connue (même URL, même source) est mise à jour plutôt
 * que dupliquée — c'est la ré-indexation planifiée (section 2.1).
 *
 * Appelée uniquement par `CrawlKnowledgeSourceJob`, en file.
 */
final class KnowledgeCrawlService
{
    public function __construct(
        private readonly KnowledgeSourceRepositoryContract $sources,
        private readonly KnowledgeDocumentRepositoryContract $documents,
        private readonly WebCrawlerContract $crawler,
    ) {}

    public function crawl(string $sourceId): void
    {
        $source = $this->sources->findOrFailById($sourceId);

        try {
            $pages = $this->crawler->crawl((string) $source->website_url, $source->website_sitemap_url);

            foreach ($pages as $page) {
                $existing = $this->documents->findByOriginUrl($source->id, $page->url);

                if ($existing !== null) {
                    $document = $this->documents->update($existing, [
                        'title' => $page->title,
                        'raw_content' => $page->text,
                        'status' => KnowledgeDocumentStatus::Pending,
                        'status_message' => null,
                    ]);
                } else {
                    $document = $this->documents->create([
                        'workspace_id' => $source->workspace_id,
                        'source_id' => $source->id,
                        'type' => KnowledgeDocumentType::WebsitePage,
                        'title' => $page->title,
                        'origin_url' => $page->url,
                        'raw_content' => $page->text,
                        'status' => KnowledgeDocumentStatus::Pending,
                    ]);
                }

                IndexKnowledgeDocumentJob::dispatch($document->id);
            }

            $this->sources->markCrawled($source);
        } catch (Throwable $exception) {
            Log::warning("Échec de l'exploration d'une source de connaissance.", [
                'source_id' => $source->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
