<?php

namespace App\Domains\Knowledge\Contracts;

use App\Domains\Knowledge\DTOs\ScoredChunk;

/**
 * Recherche les passages les plus proches d'un vecteur de requête, pour un
 * espace de travail donné.
 *
 * Implémentation actuelle : MySQL, embeddings en JSON, similarité cosinus
 * calculée en PHP (décision du 8 octobre 2026 — voir section 12 du cahier des
 * charges). Ce contrat est ce qui rend une migration vers pgvector ou un
 * service tiers possible sans changer les domaines appelants.
 */
interface EmbeddingSearchContract
{
    /**
     * @param  list<float>  $queryEmbedding
     * @return list<ScoredChunk> triés par score décroissant, au plus `$limit`
     */
    public function search(string $workspaceId, array $queryEmbedding, int $limit, ?string $sourceId = null): array;
}
