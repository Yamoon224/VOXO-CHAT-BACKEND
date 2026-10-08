<?php

namespace App\Domains\Assistant\DTOs;

/**
 * Réponse générée par le fournisseur d'IA : le contenu, les passages cités
 * (section 4.5 : chaque réponse enregistre les passages utilisés), et un
 * score de confiance [0, 1] qui décide de l'escalade.
 */
final readonly class AiReply
{
    /** @param  list<array{title: string, content: string, citation_url: string|null}>  $citations */
    public function __construct(
        public string $content,
        public array $citations,
        public float $confidence,
    ) {}
}
