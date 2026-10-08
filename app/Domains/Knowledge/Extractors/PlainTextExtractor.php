<?php

namespace App\Domains\Knowledge\Extractors;

use App\Domains\Knowledge\Contracts\TextExtractorContract;

/** TXT et Markdown : déjà du texte, aucune extraction à faire. */
final class PlainTextExtractor implements TextExtractorContract
{
    private const MIME_TYPES = ['text/plain', 'text/markdown', 'text/x-markdown'];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::MIME_TYPES, true);
    }

    public function extract(string $absolutePath): string
    {
        return (string) file_get_contents($absolutePath);
    }
}
