<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Contracts\EmbeddingProviderContract;
use App\Domains\Knowledge\Contracts\EmbeddingSearchContract;
use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeSearchContract;
use App\Domains\Knowledge\DTOs\KnowledgeSearchResult;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\KnowledgeDocument;

/**
 * Implémente la lecture étroite `KnowledgeSearchContract` : embarque la
 * requête, cherche les passages les plus proches, puis rattache chacun à son
 * document (titre, citation) — un détail que le moteur de stockage
 * (`EmbeddingSearchContract`) n'a pas à connaître.
 */
final class KnowledgeSearchService implements KnowledgeSearchContract
{
    public function __construct(
        private readonly EmbeddingProviderContract $embeddings,
        private readonly EmbeddingSearchContract $search,
        private readonly KnowledgeDocumentRepositoryContract $documents,
    ) {}

    public function search(WorkspaceScope $scope, string $query, int $limit = 5): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $vectors = $this->embeddings->embed([$query]);

        if ($vectors === []) {
            return [];
        }

        $scoredChunks = $this->search->search($scope->workspaceId, $vectors[0], $limit);

        $documentsById = [];

        $results = [];

        foreach ($scoredChunks as $scoredChunk) {
            $document = $documentsById[$scoredChunk->documentId]
                ??= $this->documents->findInWorkspaceOrFail($scope->workspaceId, $scoredChunk->documentId);

            $results[] = new KnowledgeSearchResult(
                $scoredChunk->documentId,
                $document->title,
                $scoredChunk->content,
                $scoredChunk->score,
                $this->citationUrl($document),
            );
        }

        return $results;
    }

    private function citationUrl(KnowledgeDocument $document): ?string
    {
        return $document->type === KnowledgeDocumentType::WebsitePage ? $document->origin_url : null;
    }
}
