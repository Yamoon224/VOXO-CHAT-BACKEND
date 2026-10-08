<?php

namespace App\Domains\Knowledge\Repositories;

use App\Domains\Knowledge\Contracts\KnowledgeChunkRepositoryContract;
use App\Domains\Knowledge\Support\DocumentChunker;
use App\Models\KnowledgeChunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentKnowledgeChunkRepository implements KnowledgeChunkRepositoryContract
{
    public function replaceForDocument(string $documentId, string $workspaceId, array $chunks): Collection
    {
        KnowledgeChunk::query()->where('document_id', $documentId)->delete();

        $created = [];

        foreach (array_values($chunks) as $position => $chunk) {
            $created[] = KnowledgeChunk::create([
                'workspace_id' => $workspaceId,
                'document_id' => $documentId,
                'position' => $position,
                'content' => $chunk['content'],
                'token_count' => $chunk['token_count'] ?? DocumentChunker::estimateTokenCount($chunk['content']),
            ]);
        }

        return new Collection($created);
    }

    public function unembedded(string $documentId): Collection
    {
        return KnowledgeChunk::query()
            ->where('document_id', $documentId)
            ->whereNull('embedding')
            ->orderBy('position')
            ->get();
    }

    public function saveEmbedding(KnowledgeChunk $chunk, array $embedding, string $model): void
    {
        $chunk->update(['embedding' => $embedding, 'embedding_model' => $model]);
    }

    public function embeddedForWorkspace(string $workspaceId, ?string $sourceId = null): Collection
    {
        return KnowledgeChunk::query()
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('embedding')
            ->when($sourceId !== null, fn (Builder $query) => $query->whereHas(
                'document',
                fn (Builder $document) => $document->where('source_id', $sourceId),
            ))
            ->get();
    }
}
