<?php

namespace App\Domains\Knowledge\DTOs;

/**
 * Un passage retrouvé par la recherche sémantique, avec son score de
 * similarité. Ne porte que ce que le moteur de stockage connaît ; le
 * rattachement au document (titre, source) se fait au niveau du service de
 * recherche, pas ici — `EmbeddingSearchContract` ignore tout du domaine
 * au-delà du passage et de son espace de travail.
 */
final readonly class ScoredChunk
{
    public function __construct(
        public string $chunkId,
        public string $documentId,
        public string $content,
        public float $score,
    ) {}
}
