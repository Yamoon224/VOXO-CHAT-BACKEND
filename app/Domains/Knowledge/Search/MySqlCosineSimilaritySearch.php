<?php

namespace App\Domains\Knowledge\Search;

use App\Domains\Knowledge\Contracts\EmbeddingSearchContract;
use App\Domains\Knowledge\Contracts\KnowledgeChunkRepositoryContract;
use App\Domains\Knowledge\DTOs\ScoredChunk;
use App\Domains\Knowledge\Support\CosineSimilarity;

/**
 * Implémentation MySQL de la recherche sémantique : charge les passages
 * embarqués de l'espace de travail et calcule la similarité en PHP.
 *
 * Décision du 8 octobre 2026 (section 12 du cahier des charges) : pas de
 * pgvector pour l'instant. Tient tant que le volume de passages par espace
 * reste de l'ordre de quelques milliers ; au-delà, seule cette classe change.
 */
final class MySqlCosineSimilaritySearch implements EmbeddingSearchContract
{
    public function __construct(private readonly KnowledgeChunkRepositoryContract $chunks) {}

    public function search(string $workspaceId, array $queryEmbedding, int $limit, ?string $sourceId = null): array
    {
        $scored = $this->chunks->embeddedForWorkspace($workspaceId, $sourceId)
            ->map(fn ($chunk) => new ScoredChunk(
                $chunk->id,
                $chunk->document_id,
                $chunk->content,
                CosineSimilarity::between($queryEmbedding, $chunk->embedding ?? []),
            ))
            ->sortByDesc(fn (ScoredChunk $chunk) => $chunk->score)
            ->take($limit)
            ->values();

        return $scored->all();
    }
}
