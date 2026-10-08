<?php

namespace App\Domains\Knowledge\Embeddings;

use App\Domains\Knowledge\Contracts\EmbeddingProviderContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class VoyageAiEmbeddingProvider implements EmbeddingProviderContract
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
    ) {}

    public function embed(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        if ($this->apiKey === '') {
            throw new RuntimeException('VOYAGE_API_KEY est absente : les embeddings Voyage AI ne peuvent pas être calculés.');
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->timeout(60)
            ->post('/embeddings', [
                'model' => $this->model,
                'input' => $texts,
                'input_type' => 'document',
            ])
            ->throw();

        $data = $response->json('data', []);

        return array_map(
            fn (array $item) => array_map('floatval', $item['embedding']),
            is_array($data) ? $data : [],
        );
    }

    public function modelName(): string
    {
        return $this->model;
    }
}
