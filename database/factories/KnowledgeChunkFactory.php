<?php

namespace Database\Factories;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeChunk> */
class KnowledgeChunkFactory extends Factory
{
    protected $model = KnowledgeChunk::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $document = KnowledgeDocument::factory()->create();

        return [
            'workspace_id' => $document->workspace_id,
            'document_id' => $document->id,
            'position' => 0,
            'content' => $this->faker->paragraph(),
            'token_count' => 120,
        ];
    }

    /** @param  list<float>  $embedding */
    public function embedded(array $embedding, string $model = 'test-embeddings'): static
    {
        return $this->state(fn () => ['embedding' => $embedding, 'embedding_model' => $model]);
    }
}
