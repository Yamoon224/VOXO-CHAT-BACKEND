<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeSourceRepositoryContract;
use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Enums\RecrawlFrequency;
use App\Domains\Knowledge\Exceptions\WebsiteSourceConfigurationInvalidException;
use App\Domains\Knowledge\Exceptions\WebsiteSourceRequiredException;
use App\Domains\Knowledge\Jobs\CrawlKnowledgeSourceJob;
use App\Domains\Knowledge\Jobs\IndexKnowledgeDocumentJob;
use App\Domains\Knowledge\Support\KnowledgeFileStorage;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\KnowledgeSource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

final class KnowledgeSourceService
{
    public function __construct(
        private readonly KnowledgeSourceRepositoryContract $sources,
        private readonly KnowledgeDocumentRepositoryContract $documents,
        private readonly KnowledgeFileStorage $files,
        private readonly TransactionManagerContract $transactions,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, KnowledgeSource>
     */
    public function list(WorkspaceScope $scope, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->sources->paginate($scope->workspaceId, $filters, $perPage);
    }

    /**
     * Crée une source d'import et un document par fichier reçu, chacun mis en
     * file pour extraction/découpage/embeddings.
     *
     * @param  list<UploadedFile>  $files
     */
    public function createUploadSource(WorkspaceScope $scope, string $name, array $files, string $userId): KnowledgeSource
    {
        $source = $this->transactions->run(function () use ($scope, $name, $userId): KnowledgeSource {
            return $this->sources->create([
                'workspace_id' => $scope->workspaceId,
                'type' => KnowledgeSourceType::Upload,
                'name' => $name,
                'created_by_user_id' => $userId,
            ]);
        });

        foreach ($files as $file) {
            $diskPath = $this->files->store($scope->workspaceId, $file);

            $document = $this->documents->create([
                'workspace_id' => $scope->workspaceId,
                'source_id' => $source->id,
                'type' => KnowledgeDocumentType::File,
                'title' => $file->getClientOriginalName(),
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'disk_path' => $diskPath,
                'status' => KnowledgeDocumentStatus::Pending,
                'created_by_user_id' => $userId,
            ]);

            IndexKnowledgeDocumentJob::dispatch($document->id);
        }

        return $source;
    }

    /** @throws WebsiteSourceConfigurationInvalidException */
    public function createWebsiteSource(
        WorkspaceScope $scope,
        ?string $name,
        string $url,
        ?string $sitemapUrl,
        RecrawlFrequency $frequency,
        string $userId,
    ): KnowledgeSource {
        if (trim($url) === '') {
            throw WebsiteSourceConfigurationInvalidException::make();
        }

        $source = $this->sources->create([
            'workspace_id' => $scope->workspaceId,
            'type' => KnowledgeSourceType::Website,
            'name' => $name ?? $url,
            'website_url' => $url,
            'website_sitemap_url' => $sitemapUrl,
            'recrawl_frequency' => $frequency,
            'created_by_user_id' => $userId,
        ]);

        CrawlKnowledgeSourceJob::dispatch($source->id);

        return $source;
    }

    /** @throws WebsiteSourceRequiredException */
    public function recrawl(WorkspaceScope $scope, string $sourceId): void
    {
        $source = $this->sources->findInWorkspaceOrFail($scope->workspaceId, $sourceId);

        if ($source->type !== KnowledgeSourceType::Website) {
            throw WebsiteSourceRequiredException::make();
        }

        CrawlKnowledgeSourceJob::dispatch($source->id);
    }

    public function delete(WorkspaceScope $scope, string $sourceId): void
    {
        $source = $this->sources->findInWorkspaceOrFail($scope->workspaceId, $sourceId);

        foreach ($this->documents->forSource($source->id) as $document) {
            if ($document->disk_path !== null) {
                $this->files->delete($document->disk_path);
            }
        }

        $this->sources->delete($source);
    }
}
