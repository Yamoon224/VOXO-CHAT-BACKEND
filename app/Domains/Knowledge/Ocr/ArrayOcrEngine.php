<?php

namespace App\Domains\Knowledge\Ocr;

use App\Domains\Knowledge\Contracts\OcrEngineContract;

/**
 * Doublure de test : n'appelle aucune API, renvoie un texte configurable.
 * Enregistrée comme singleton (voir `DomainServiceProvider`) pour que les
 * tests redemandent exactement l'instance utilisée par l'application.
 */
final class ArrayOcrEngine implements OcrEngineContract
{
    private string $response = '';

    /** @var list<array{path: string, mime_type: string}> */
    private array $calls = [];

    public function respondWith(string $text): void
    {
        $this->response = $text;
    }

    public function extractText(string $absolutePath, string $mimeType): string
    {
        $this->calls[] = ['path' => $absolutePath, 'mime_type' => $mimeType];

        return $this->response;
    }

    /** @return list<array{path: string, mime_type: string}> */
    public function calls(): array
    {
        return $this->calls;
    }
}
