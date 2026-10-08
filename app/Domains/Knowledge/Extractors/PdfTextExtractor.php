<?php

namespace App\Domains\Knowledge\Extractors;

use App\Domains\Knowledge\Contracts\TextExtractorContract;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * PDF texte (non scanné). Un PDF scanné renvoie ici un texte vide ou
 * inexploitable ; l'OCR prend le relais côté `KnowledgeIngestionService`,
 * pas ici — cet extracteur ne sait que lire ce qui est déjà du texte.
 */
final class PdfTextExtractor implements TextExtractorContract
{
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'application/pdf';
    }

    public function extract(string $absolutePath): string
    {
        try {
            return (new Parser)->parseFile($absolutePath)->getText();
        } catch (Throwable) {
            // PDF corrompu ou entièrement scanné : laisse l'appelant retomber sur l'OCR.
            return '';
        }
    }
}
