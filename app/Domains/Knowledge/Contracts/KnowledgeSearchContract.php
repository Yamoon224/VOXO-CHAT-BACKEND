<?php

namespace App\Domains\Knowledge\Contracts;

use App\Domains\Knowledge\DTOs\KnowledgeSearchResult;

/**
 * Lecture étroite exposée aux autres domaines (l'agent IA du domaine
 * `Assistant`, lot 2) : ils cherchent dans la base de connaissances, ils
 * n'indexent rien.
 *
 * Prend un identifiant d'espace de travail nu, pas un `WorkspaceScope` : la
 * recherche n'a besoin que du périmètre, pas de qui appelle. L'agent IA
 * déclenché après un message de visiteur n'a pas d'appelant authentifié à
 * faire porter par un `WorkspaceScope`.
 */
interface KnowledgeSearchContract
{
    /** @return list<KnowledgeSearchResult> */
    public function search(string $workspaceId, string $query, int $limit = 5): array;
}
