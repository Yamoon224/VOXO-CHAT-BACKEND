<?php

use App\Domains\Knowledge\Crawling\ArrayWebCrawler;
use App\Domains\Knowledge\Crawling\SimpleWebCrawler;
use App\Domains\Knowledge\Embeddings\ArrayEmbeddingProvider;
use App\Domains\Knowledge\Embeddings\VoyageAiEmbeddingProvider;
use App\Domains\Knowledge\Ocr\ArrayOcrEngine;
use App\Domains\Knowledge\Ocr\ClaudeOcrEngine;

/*
|--------------------------------------------------------------------------
| Base de connaissances (lot 1)
|--------------------------------------------------------------------------
|
| Les domaines ne connaissent que les contrats (`EmbeddingProviderContract`,
| `OcrEngineContract`, `WebCrawlerContract`) : le prestataire se choisit ici.
|
| Décisions du 8 octobre 2026 (section 12 du cahier des charges) : Voyage AI
| pour les embeddings, MySQL (pas de pgvector) pour la recherche vectorielle,
| Claude pour l'OCR.
|
*/

return [

    'storage_disk' => env('KNOWLEDGE_STORAGE_DISK', 'local'),

    'max_upload_kb' => (int) env('KNOWLEDGE_MAX_UPLOAD_KB', 20480),

    'chunk' => [
        'max_chars' => 1500,
        'overlap_chars' => 150,
    ],

    'embeddings' => [
        'driver' => env('KNOWLEDGE_EMBEDDINGS_DRIVER', 'voyage'),

        'drivers' => [
            'voyage' => VoyageAiEmbeddingProvider::class,
            // Doublure sans appel réseau : pilote de la suite de tests.
            'array' => ArrayEmbeddingProvider::class,
        ],

        'voyage' => [
            'key' => env('VOYAGE_API_KEY'),
            'model' => env('VOYAGE_EMBEDDING_MODEL', 'voyage-3.5'),
            'base_url' => env('VOYAGE_BASE_URL', 'https://api.voyageai.com/v1'),
        ],
    ],

    'ocr' => [
        'driver' => env('KNOWLEDGE_OCR_DRIVER', 'claude'),

        'drivers' => [
            'claude' => ClaudeOcrEngine::class,
            'array' => ArrayOcrEngine::class,
        ],

        'claude' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_OCR_MODEL', 'claude-haiku-4-5'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        ],
    ],

    'crawler' => [
        'driver' => env('KNOWLEDGE_CRAWLER_DRIVER', 'http'),

        'drivers' => [
            'http' => SimpleWebCrawler::class,
            'array' => ArrayWebCrawler::class,
        ],

        'max_pages_per_crawl' => (int) env('KNOWLEDGE_CRAWLER_MAX_PAGES', 50),
    ],

];
