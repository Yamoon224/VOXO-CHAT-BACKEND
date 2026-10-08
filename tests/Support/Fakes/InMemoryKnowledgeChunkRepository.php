<?php

namespace Tests\Support\Fakes;

use App\Domains\Knowledge\Contracts\KnowledgeChunkRepositoryContract;
use App\Domains\Knowledge\Support\DocumentChunker;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Collection;

final class InMemoryKnowledgeChunkRepository implements KnowledgeChunkRepositoryContract
{
    /** @var array<string, KnowledgeChunk> */
    private array $chunks = [];

    public function replaceForDocument(string $documentId, string $workspaceId, array $chunks): Collection
    {
        foreach ($this->chunks as $id => $chunk) {
            if ($chunk->document_id === $documentId) {
                unset($this->chunks[$id]);
            }
        }

        $created = [];

        foreach (array_values($chunks) as $position => $chunk) {
            $model = ModelFactory::knowledgeChunk([
                'workspace_id' => $workspaceId,
                'document_id' => $documentId,
                'position' => $position,
                'content' => $chunk['content'],
                'token_count' => $chunk['token_count'] ?? DocumentChunker::estimateTokenCount($chunk['content']),
            ]);

            $this->chunks[$model->id] = $model;
            $created[] = $model;
        }

        return new Collection($created);
    }

    public function unembedded(string $documentId): Collection
    {
        return (new Collection($this->chunks))
            ->filter(fn (KnowledgeChunk $chunk) => $chunk->document_id === $documentId && $chunk->embedding === null)
            ->sortBy(fn (KnowledgeChunk $chunk) => $chunk->position)
            ->values();
    }

    public function saveEmbedding(KnowledgeChunk $chunk, array $embedding, string $model): void
    {
        $chunk->setRawAttributes([
            'embedding' => json_encode($embedding),
            'embedding_model' => $model,
        ] + $chunk->getAttributes(), true);
    }

    public function embeddedForWorkspace(string $workspaceId, ?string $sourceId = null): Collection
    {
        return (new Collection($this->chunks))
            ->filter(fn (KnowledgeChunk $chunk) => $chunk->workspace_id === $workspaceId && $chunk->embedding !== null)
            ->values();
    }
}
