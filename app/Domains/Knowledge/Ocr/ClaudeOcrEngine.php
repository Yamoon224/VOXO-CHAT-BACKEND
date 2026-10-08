<?php

namespace App\Domains\Knowledge\Ocr;

use App\Domains\Knowledge\Contracts\OcrEngineContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OCR par lecture d'image : envoie le fichier à l'API Claude (bloc de contenu
 * image ou document) et lui demande de transcrire le texte verbatim.
 *
 * Décision du 8 octobre 2026 (section 12 du cahier des charges) : pas de
 * binaire Tesseract à installer sur l'environnement actuel.
 */
final class ClaudeOcrEngine implements OcrEngineContract
{
    private const IMAGE_MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    private const PROMPT = 'Transcris verbatim tout le texte visible dans ce document, sans commentaire ni résumé. Réponds uniquement avec le texte transcrit.';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
    ) {}

    public function extractText(string $absolutePath, string $mimeType): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException("ANTHROPIC_API_KEY est absente : l'OCR par Claude ne peut pas appeler l'API.");
        }

        $block = $this->contentBlock($absolutePath, $mimeType);

        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(60)
            ->post('/messages', [
                'model' => $this->model,
                'max_tokens' => 4096,
                'messages' => [[
                    'role' => 'user',
                    'content' => [$block, ['type' => 'text', 'text' => self::PROMPT]],
                ]],
            ])
            ->throw();

        $parts = $response->json('content', []);

        return implode('', array_map(
            fn (array $part) => (string) ($part['text'] ?? ''),
            is_array($parts) ? $parts : [],
        ));
    }

    /** @return array<string, mixed> */
    private function contentBlock(string $absolutePath, string $mimeType): array
    {
        $data = base64_encode((string) file_get_contents($absolutePath));

        if (in_array($mimeType, self::IMAGE_MIME_TYPES, true)) {
            return [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $data],
            ];
        }

        return [
            'type' => 'document',
            'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $data],
        ];
    }
}
