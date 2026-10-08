<?php

namespace App\Domains\Knowledge\Extractors;

use App\Domains\Knowledge\Contracts\TextExtractorContract;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;

final class DocxTextExtractor implements TextExtractorContract
{
    private const MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::MIME_TYPES, true);
    }

    public function extract(string $absolutePath): string
    {
        $document = IOFactory::load($absolutePath);
        $lines = [];

        foreach ($document->getSections() as $section) {
            $this->collectText($section, $lines);
        }

        return implode("\n", $lines);
    }

    /** @param  list<string>  $lines */
    private function collectText(AbstractContainer $container, array &$lines): void
    {
        foreach ($container->getElements() as $element) {
            if ($element instanceof Text) {
                $lines[] = $element->getText();
            } elseif ($element instanceof TextRun) {
                $lines[] = $element->getText();
            } elseif ($element instanceof AbstractContainer) {
                $this->collectText($element, $lines);
            }
        }
    }
}
