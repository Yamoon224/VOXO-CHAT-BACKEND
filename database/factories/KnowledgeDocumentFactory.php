<?php

namespace Database\Factories;

use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeDocument> */
class KnowledgeDocumentFactory extends Factory
{
    protected $model = KnowledgeDocument::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $source = KnowledgeSource::factory()->create();

        return [
            'workspace_id' => $source->workspace_id,
            'source_id' => $source->id,
            'type' => KnowledgeDocumentType::File,
            'title' => $this->faker->sentence(4),
            'raw_content' => $this->faker->paragraphs(3, true),
            'status' => KnowledgeDocumentStatus::Pending,
        ];
    }

    public function indexed(int $chunkCount = 2): static
    {
        return $this->state(fn () => [
            'status' => KnowledgeDocumentStatus::Indexed,
            'chunk_count' => $chunkCount,
            'indexed_at' => now(),
        ]);
    }

    public function failed(string $message = 'Échec simulé.'): static
    {
        return $this->state(fn () => [
            'status' => KnowledgeDocumentStatus::Failed,
            'status_message' => $message,
        ]);
    }

    public function qa(string $question, string $answer): static
    {
        return $this->state(fn () => [
            'type' => KnowledgeDocumentType::Qa,
            'title' => $question,
            'question' => $question,
            'answer' => $answer,
            'raw_content' => "Q: {$question}\nA: {$answer}",
        ]);
    }
}
