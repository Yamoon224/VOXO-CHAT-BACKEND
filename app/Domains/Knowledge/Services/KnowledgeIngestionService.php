<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Contracts\EmbeddingProviderContract;
use App\Domains\Knowledge\Contracts\KnowledgeChunkRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Knowledge\Contracts\OcrEngineContract;
use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Support\DocumentChunker;
use App\Domains\Knowledge\Support\KnowledgeFileStorage;
use App\Domains\Knowledge\Support\TextExtractorRegistry;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Chaîne d'ingestion d'un document : extraction (avec repli OCR), découpage,
 * embeddings, indexation. Chaque étape ne refait pas le travail déjà fait —
 * c'est ce qui rend `retry` sûr après un échec partiel (reprise sur échec,
 * exigée par le cahier des charges section 2.1).
 *
 * N'est jamais appelée directement par un contrôleur : seulement par
 * `IndexKnowledgeDocumentJob`, en file. Une erreur est absorbée ici (statut
 * `failed` + message) plutôt que relancée, pour que l'échec apparaisse sur le
 * document plutôt que de boucler indéfiniment sur la file.
 */
final class KnowledgeIngestionService
{
    public function __construct(
        private readonly KnowledgeDocumentRepositoryContract $documents,
        private readonly KnowledgeChunkRepositoryContract $chunks,
        private readonly TextExtractorRegistry $extractors,
        private readonly OcrEngineContract $ocr,
        private readonly EmbeddingProviderContract $embeddings,
        private readonly KnowledgeFileStorage $files,
    ) {}

    public function index(string $documentId): void
    {
        $document = $this->documents->findOrFailById($documentId);
        $this->documents->update($document, ['status' => KnowledgeDocumentStatus::Processing, 'status_message' => null]);

        try {
            $rawContent = $document->raw_content;

            if ($rawContent === null || $rawContent === '') {
                $rawContent = $this->extractContent($document);
                $this->documents->update($document, ['raw_content' => $rawContent]);
            }

            $chunks = $this->chunks->unembedded($document->id);

            if ($chunks->isEmpty()) {
                $pieces = DocumentChunker::chunk($rawContent);
                $chunks = $this->chunks->replaceForDocument(
                    $document->id,
                    $document->workspace_id,
                    array_map(fn (string $piece) => ['content' => $piece], $pieces),
                );
            }

            if ($chunks->isNotEmpty()) {
                $vectors = $this->embeddings->embed($chunks->pluck('content')->all());

                foreach ($chunks as $position => $chunk) {
                    $this->chunks->saveEmbedding($chunk, $vectors[$position], $this->embeddings->modelName());
                }
            }

            $this->documents->update($document, [
                'status' => KnowledgeDocumentStatus::Indexed,
                'chunk_count' => $chunks->count(),
                'indexed_at' => now(),
                'status_message' => null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Échec de l\'indexation d\'un document de connaissance.', [
                'document_id' => $document->id,
                'error' => $exception->getMessage(),
            ]);

            $this->documents->update($document, [
                'status' => KnowledgeDocumentStatus::Failed,
                'status_message' => $exception->getMessage(),
            ]);
        }
    }

    private function extractContent(KnowledgeDocument $document): string
    {
        if ($document->type !== KnowledgeDocumentType::File || $document->disk_path === null) {
            return (string) $document->raw_content;
        }

        $path = $this->files->absolutePath($document->disk_path);
        $mimeType = (string) $document->mime_type;

        $text = $this->extractors->supports($mimeType)
            ? trim($this->extractors->extract($path, $mimeType))
            : '';

        if ($text !== '') {
            return $text;
        }

        // Texte vide : fichier image, ou PDF entièrement scanné. L'OCR prend le relais.
        return trim($this->ocr->extractText($path, $mimeType));
    }
}
