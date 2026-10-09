<?php

namespace Tests\Unit\Knowledge;

use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Exceptions\DocumentNotRetryableException;
use App\Domains\Knowledge\Exceptions\QaContentRequiredException;
use App\Domains\Knowledge\Jobs\IndexKnowledgeDocumentJob;
use App\Domains\Knowledge\Services\KnowledgeDocumentService;
use App\Domains\Knowledge\Support\KnowledgeFileStorage;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryKnowledgeChunkRepository;
use Tests\Support\Fakes\InMemoryKnowledgeDocumentRepository;
use Tests\Support\Fakes\InMemoryKnowledgeSourceRepository;
use Tests\Support\Fakes\InMemoryQuotaGuard;
use Tests\TestCase;

class KnowledgeDocumentServiceTest extends TestCase
{
    private function scope(): WorkspaceScope
    {
        return new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);
    }

    #[Test]
    public function seul_un_document_en_echec_peut_etre_redeclenche(): void
    {
        $documents = new InMemoryKnowledgeDocumentRepository;
        $document = $documents->create([
            'workspace_id' => 'workspace-1', 'source_id' => 'source-1', 'type' => 'file',
            'title' => 'x', 'status' => KnowledgeDocumentStatus::Indexed,
        ]);

        $service = new KnowledgeDocumentService($documents, new InMemoryKnowledgeSourceRepository, new InMemoryKnowledgeChunkRepository, new KnowledgeFileStorage('local'), new InMemoryQuotaGuard);

        $this->expectException(DocumentNotRetryableException::class);

        $service->retry($this->scope(), $document->id);
    }

    #[Test]
    public function redeclencher_un_document_en_echec_le_remet_en_attente_et_le_met_en_file(): void
    {
        Bus::fake();

        $documents = new InMemoryKnowledgeDocumentRepository;
        $document = $documents->create([
            'workspace_id' => 'workspace-1', 'source_id' => 'source-1', 'type' => 'file',
            'title' => 'x', 'status' => KnowledgeDocumentStatus::Failed, 'status_message' => 'Oups.',
        ]);

        $service = new KnowledgeDocumentService($documents, new InMemoryKnowledgeSourceRepository, new InMemoryKnowledgeChunkRepository, new KnowledgeFileStorage('local'), new InMemoryQuotaGuard);

        $retried = $service->retry($this->scope(), $document->id);

        $this->assertSame(KnowledgeDocumentStatus::Pending, $retried->status);
        $this->assertNull($retried->status_message);
        Bus::assertDispatched(fn (IndexKnowledgeDocumentJob $job) => $job->documentId === $document->id);
    }

    #[Test]
    public function une_question_ou_reponse_vide_est_refusee(): void
    {
        $service = new KnowledgeDocumentService(
            new InMemoryKnowledgeDocumentRepository,
            new InMemoryKnowledgeSourceRepository,
            new InMemoryKnowledgeChunkRepository,
            new KnowledgeFileStorage('local'),
            new InMemoryQuotaGuard,
        );

        $this->expectException(QaContentRequiredException::class);

        $service->createQaEntry($this->scope(), '  ', 'Une réponse.', 'user-1');
    }

    #[Test]
    public function deux_entrees_manuelles_du_meme_espace_partagent_la_meme_source(): void
    {
        Bus::fake();

        $sources = new InMemoryKnowledgeSourceRepository;
        $service = new KnowledgeDocumentService(new InMemoryKnowledgeDocumentRepository, $sources, new InMemoryKnowledgeChunkRepository, new KnowledgeFileStorage('local'), new InMemoryQuotaGuard);

        $first = $service->createQaEntry($this->scope(), 'Quels sont vos horaires ?', 'De 9h à 18h.', 'user-1');
        $second = $service->createQaEntry($this->scope(), 'Livrez-vous le dimanche ?', 'Non.', 'user-1');

        $this->assertSame($first->source_id, $second->source_id);
        $this->assertSame(KnowledgeSourceType::Manual, $sources->findInWorkspaceOrFail('workspace-1', $first->source_id)->type);
    }
}
