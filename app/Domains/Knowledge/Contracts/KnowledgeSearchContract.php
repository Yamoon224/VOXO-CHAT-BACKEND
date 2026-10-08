<?php

namespace App\Domains\Knowledge\Contracts;

use App\Domains\Knowledge\DTOs\KnowledgeSearchResult;
use App\Domains\Shared\Support\WorkspaceScope;

/**
 * Lecture étroite exposée aux autres domaines (l'agent IA du domaine
 * `Assistant`, au lot 2) : ils cherchent dans la base de connaissances, ils
 * n'indexent rien.
 */
interface KnowledgeSearchContract
{
    /** @return list<KnowledgeSearchResult> */
    public function search(WorkspaceScope $scope, string $query, int $limit = 5): array;
}
