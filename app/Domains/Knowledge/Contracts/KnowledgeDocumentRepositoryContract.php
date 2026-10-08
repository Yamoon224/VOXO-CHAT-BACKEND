<?php

namespace App\Domains\Knowledge\Contracts;

use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface KnowledgeDocumentRepositoryContract
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, KnowledgeDocument>
     */
    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): KnowledgeDocument;

    /**
     * Chargement sans périmètre, pour la chaîne d'ingestion en file : le job
     * ne porte pas de jeton d'appelant, seul l'identifiant généré à la
     * création du document (jamais fourni par un client).
     */
    public function findOrFailById(string $id): KnowledgeDocument;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): KnowledgeDocument;

    /** @param  array<string, mixed>  $attributes */
    public function update(KnowledgeDocument $document, array $attributes): KnowledgeDocument;

    public function findByOriginUrl(string $sourceId, string $originUrl): ?KnowledgeDocument;

    public function delete(KnowledgeDocument $document): void;

    /** @return Collection<int, KnowledgeDocument> */
    public function forSource(string $sourceId): Collection;
}
