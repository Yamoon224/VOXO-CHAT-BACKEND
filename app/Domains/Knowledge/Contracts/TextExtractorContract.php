<?php

namespace App\Domains\Knowledge\Contracts;

/**
 * Extrait le texte brut d'un fichier d'un format donné. Une implémentation
 * par format (PDF, DOCX, tableur, texte brut) ; ajouter un format revient à
 * ajouter une classe, sans toucher à la chaîne d'ingestion.
 */
interface TextExtractorContract
{
    public function supports(string $mimeType): bool;

    public function extract(string $absolutePath): string;
}
