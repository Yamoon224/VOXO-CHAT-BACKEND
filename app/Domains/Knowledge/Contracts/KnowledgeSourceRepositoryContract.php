<?php

namespace App\Domains\Knowledge\Contracts;

use App\Models\KnowledgeSource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface KnowledgeSourceRepositoryContract
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, KnowledgeSource>
     */
    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): KnowledgeSource;

    /**
     * Chargement sans périmètre, pour la chaîne d'ingestion en file : le job
     * ne porte qu'un identifiant de source, généré à la création — jamais
     * fourni par un client.
     */
    public function findOrFailById(string $id): KnowledgeSource;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): KnowledgeSource;

    public function findManualSource(string $workspaceId): ?KnowledgeSource;

    public function delete(KnowledgeSource $source): void;

    public function markCrawled(KnowledgeSource $source): KnowledgeSource;

    /**
     * Sources de type site web dont la ré-indexation planifiée est due,
     * tous espaces de travail confondus : balayée par la tâche planifiée,
     * pas par un appelant borné à un espace.
     *
     * @return Collection<int, KnowledgeSource>
     */
    public function dueForRecrawl(): Collection;
}
