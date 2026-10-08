<?php

namespace App\Domains\Knowledge\DTOs;

/**
 * Résultat de recherche exposé aux autres domaines (l'agent IA cherche, il
 * n'indexe pas — voir `KnowledgeSearchContract`).
 */
final readonly class KnowledgeSearchResult
{
    public function __construct(
        public string $documentId,
        public string $documentTitle,
        public string $chunkContent,
        public float $score,
        public ?string $citationUrl = null,
    ) {}
}
