<?php

namespace App\Domains\Knowledge\Support;

use App\Domains\Knowledge\Contracts\TextExtractorContract;
use App\Domains\Knowledge\Exceptions\UnsupportedFileTypeException;

/**
 * Choisit l'extracteur de texte adapté à un type MIME, parmi tous ceux
 * déclarés. Ajouter un format revient à ajouter une classe à la liste reçue
 * ici (câblée dans `DomainServiceProvider`), sans toucher à l'ingestion.
 */
final class TextExtractorRegistry
{
    /** @param  list<TextExtractorContract>  $extractors */
    public function __construct(private readonly array $extractors) {}

    /** @throws UnsupportedFileTypeException */
    public function extract(string $absolutePath, string $mimeType): string
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($mimeType)) {
                return $extractor->extract($absolutePath);
            }
        }

        throw UnsupportedFileTypeException::make($mimeType);
    }

    public function supports(string $mimeType): bool
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($mimeType)) {
                return true;
            }
        }

        return false;
    }
}
