<?php

namespace Tests\Unit\Knowledge;

use App\Domains\Knowledge\Contracts\EmbeddingProviderContract;
use App\Domains\Knowledge\Embeddings\ArrayEmbeddingProvider;
use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Extractors\PlainTextExtractor;
use App\Domains\Knowledge\Ocr\ArrayOcrEngine;
use App\Domains\Knowledge\Services\KnowledgeIngestionService;
use App\Domains\Knowledge\Support\KnowledgeFileStorage;
use App\Domains\Knowledge\Support\TextExtractorRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\Fakes\InMemoryKnowledgeChunkRepository;
use Tests\Support\Fakes\InMemoryKnowledgeDocumentRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class KnowledgeIngestionServiceTest extends TestCase
{
    #[Test]
    public function une_entree_manuelle_passe_directement_au_decoupage_sans_extraction(): void
    {
        $document = ModelFactory::knowledgeDocument([
            'type' => KnowledgeDocumentType::Qa,
            'raw_content' => "Q: Quels sont vos horaires ?\nA: Nous sommes ouverts de 9h à 18h.",
        ]);

        $documents = new InMemoryKnowledgeDocumentRepository($document);
        $chunks = new InMemoryKnowledgeChunkRepository;
        $ocr = new ArrayOcrEngine;

        $service = new KnowledgeIngestionService(
            $documents,
            $chunks,
            new TextExtractorRegistry([]),
            $ocr,
            new ArrayEmbeddingProvider,
            new KnowledgeFileStorage('local'),
        );

        $service->index($document->id);

        $this->assertSame(KnowledgeDocumentStatus::Indexed, $document->status);
        $this->assertGreaterThan(0, $document->chunk_count);
        $this->assertSame([], $ocr->calls());
    }

    #[Test]
    public function un_fichier_texte_est_extrait_puis_embarque(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('notes.txt', 'Le support est disponible par e-mail et par chat.');
        $storage = new KnowledgeFileStorage('local');
        $diskPath = $storage->store('workspace-1', $file);

        $document = ModelFactory::knowledgeDocument([
            'type' => KnowledgeDocumentType::File,
            'disk_path' => $diskPath,
            'mime_type' => 'text/plain',
            'raw_content' => null,
        ]);

        $documents = new InMemoryKnowledgeDocumentRepository($document);

        $service = new KnowledgeIngestionService(
            $documents,
            new InMemoryKnowledgeChunkRepository,
            new TextExtractorRegistry([new PlainTextExtractor]),
            new ArrayOcrEngine,
            new ArrayEmbeddingProvider,
            $storage,
        );

        $service->index($document->id);

        $this->assertSame(KnowledgeDocumentStatus::Indexed, $document->status);
        $this->assertStringContainsString('e-mail', (string) $document->raw_content);
    }

    #[Test]
    public function un_pdf_sans_texte_exploitable_retombe_sur_l_ocr(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('scan.pdf', '%PDF-1.4 contenu binaire sans texte utile');
        $storage = new KnowledgeFileStorage('local');
        $diskPath = $storage->store('workspace-1', $file);

        $document = ModelFactory::knowledgeDocument([
            'type' => KnowledgeDocumentType::File,
            'disk_path' => $diskPath,
            'mime_type' => 'application/pdf',
            'raw_content' => null,
        ]);

        $documents = new InMemoryKnowledgeDocumentRepository($document);
        $ocr = new ArrayOcrEngine;
        $ocr->respondWith('Texte reconnu par OCR.');

        $service = new KnowledgeIngestionService(
            $documents,
            new InMemoryKnowledgeChunkRepository,
            // Aucun extracteur ne prend en charge le PDF ici : bascule directe sur l'OCR.
            new TextExtractorRegistry([]),
            $ocr,
            new ArrayEmbeddingProvider,
            $storage,
        );

        $service->index($document->id);

        $this->assertSame(KnowledgeDocumentStatus::Indexed, $document->status);
        $this->assertSame('Texte reconnu par OCR.', $document->raw_content);
        $this->assertCount(1, $ocr->calls());
    }

    #[Test]
    public function un_echec_marque_le_document_en_echec_sans_relancer_l_exception(): void
    {
        $document = ModelFactory::knowledgeDocument([
            'type' => KnowledgeDocumentType::Qa,
            'raw_content' => 'Contenu quelconque.',
        ]);

        $documents = new InMemoryKnowledgeDocumentRepository($document);

        $failingEmbeddings = new class implements EmbeddingProviderContract
        {
            public function embed(array $texts): array
            {
                throw new RuntimeException('Fournisseur indisponible.');
            }

            public function modelName(): string
            {
                return 'failing';
            }
        };

        $service = new KnowledgeIngestionService(
            $documents,
            new InMemoryKnowledgeChunkRepository,
            new TextExtractorRegistry([]),
            new ArrayOcrEngine,
            $failingEmbeddings,
            new KnowledgeFileStorage('local'),
        );

        $service->index($document->id);

        $this->assertSame(KnowledgeDocumentStatus::Failed, $document->status);
        $this->assertSame('Fournisseur indisponible.', $document->status_message);
    }

    #[Test]
    public function une_reprise_ne_refait_pas_l_extraction_ni_le_decoupage_deja_faits(): void
    {
        $document = ModelFactory::knowledgeDocument([
            'type' => KnowledgeDocumentType::File,
            'disk_path' => 'inexistant.pdf',
            'mime_type' => 'application/pdf',
            'raw_content' => 'Déjà extrait avant un échec précédent.',
            'status' => KnowledgeDocumentStatus::Failed,
        ]);

        $documents = new InMemoryKnowledgeDocumentRepository($document);
        $chunks = new InMemoryKnowledgeChunkRepository;
        $chunks->replaceForDocument($document->id, $document->workspace_id, [['content' => 'Passage déjà découpé.']]);
        $ocr = new ArrayOcrEngine;

        $service = new KnowledgeIngestionService(
            $documents,
            $chunks,
            new TextExtractorRegistry([]),
            $ocr,
            new ArrayEmbeddingProvider,
            new KnowledgeFileStorage('local'),
        );

        $service->index($document->id);

        $this->assertSame(KnowledgeDocumentStatus::Indexed, $document->status);
        // Ni l'extraction (le chemin disque n'existe pas) ni l'OCR n'ont été
        // sollicités : la reprise n'a fait qu'embarquer le passage existant.
        $this->assertSame([], $ocr->calls());
    }
}
