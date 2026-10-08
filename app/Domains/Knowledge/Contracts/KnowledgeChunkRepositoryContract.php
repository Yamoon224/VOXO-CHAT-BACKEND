<?php

namespace App\Domains\Knowledge\Contracts;

use App\Models\KnowledgeChunk;
use Illuminate\Support\Collection;

interface KnowledgeChunkRepositoryContract
{
    /**
     * Remplace tous les passages d'un document par la liste donnée : la
     * ré-indexation (reprise sur échec, ré-exploration) ne doit pas laisser
     * d'anciens passages orphelins mêlés aux nouveaux.
     *
     * @param  list<array{content: string, token_count?: int}>  $chunks
     * @return Collection<int, KnowledgeChunk>
     */
    public function replaceForDocument(string $documentId, string $workspaceId, array $chunks): Collection;

    /** @return Collection<int, KnowledgeChunk> sans vecteur d'embedding, dans l'ordre de position */
    public function unembedded(string $documentId): Collection;

    /** @param  list<float>  $embedding */
    public function saveEmbedding(KnowledgeChunk $chunk, array $embedding, string $model): void;

    /** @return Collection<int, KnowledgeChunk> tous les passages embarqués d'un espace, document chargé */
    public function embeddedForWorkspace(string $workspaceId, ?string $sourceId = null): Collection;
}
