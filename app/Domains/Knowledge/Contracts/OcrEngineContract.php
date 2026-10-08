<?php

namespace App\Domains\Knowledge\Contracts;

/**
 * Lit le texte d'une image ou d'un PDF scanné, en repli quand l'extraction de
 * texte normale ne renvoie rien d'exploitable.
 */
interface OcrEngineContract
{
    public function extractText(string $absolutePath, string $mimeType): string;
}
