<?php

namespace Tests\Unit\Knowledge;

use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Enums\RecrawlFrequency;
use App\Domains\Knowledge\Exceptions\WebsiteSourceConfigurationInvalidException;
use App\Domains\Knowledge\Exceptions\WebsiteSourceRequiredException;
use App\Domains\Knowledge\Jobs\CrawlKnowledgeSourceJob;
use App\Domains\Knowledge\Jobs\IndexKnowledgeDocumentJob;
use App\Domains\Knowledge\Services\KnowledgeSourceService;
use App\Domains\Knowledge\Support\KnowledgeFileStorage;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\ImmediateTransactionManager;
use Tests\Support\Fakes\InMemoryKnowledgeDocumentRepository;
use Tests\Support\Fakes\InMemoryKnowledgeSourceRepository;
use Tests\Support\Fakes\InMemoryQuotaGuard;
use Tests\TestCase;

class KnowledgeSourceServiceTest extends TestCase
{
    private function scope(string $workspaceId = 'workspace-1'): WorkspaceScope
    {
        return new WorkspaceScope($workspaceId, 'member-1', 'user-1', WorkspaceRole::Admin);
    }

    #[Test]
    public function importer_des_fichiers_cree_une_source_et_un_document_par_fichier(): void
    {
        Storage::fake('local');
        Bus::fake();

        $sources = new InMemoryKnowledgeSourceRepository;
        $documents = new InMemoryKnowledgeDocumentRepository;
        $service = new KnowledgeSourceService($sources, $documents, new KnowledgeFileStorage('local'), new ImmediateTransactionManager, new InMemoryQuotaGuard);

        $files = [
            UploadedFile::fake()->create('a.txt', 10, 'text/plain'),
            UploadedFile::fake()->create('b.txt', 10, 'text/plain'),
        ];

        $source = $service->createUploadSource($this->scope(), 'Mes fichiers', $files, 'user-1');

        $this->assertSame(KnowledgeSourceType::Upload, $source->type);
        $this->assertCount(2, $documents->forSource($source->id));
        Bus::assertDispatchedTimes(IndexKnowledgeDocumentJob::class, 2);
    }

    #[Test]
    public function une_source_web_sans_url_est_refusee(): void
    {
        $service = new KnowledgeSourceService(
            new InMemoryKnowledgeSourceRepository,
            new InMemoryKnowledgeDocumentRepository,
            new KnowledgeFileStorage('local'),
            new ImmediateTransactionManager,
            new InMemoryQuotaGuard,
        );

        $this->expectException(WebsiteSourceConfigurationInvalidException::class);

        $service->createWebsiteSource($this->scope(), null, '  ', null, RecrawlFrequency::Manual, 'user-1');
    }

    #[Test]
    public function creer_une_source_web_met_une_exploration_en_file(): void
    {
        Bus::fake();

        $service = new KnowledgeSourceService(
            new InMemoryKnowledgeSourceRepository,
            new InMemoryKnowledgeDocumentRepository,
            new KnowledgeFileStorage('local'),
            new ImmediateTransactionManager,
            new InMemoryQuotaGuard,
        );

        $source = $service->createWebsiteSource($this->scope(), 'Site', 'https://example.test', null, RecrawlFrequency::Daily, 'user-1');

        $this->assertSame(KnowledgeSourceType::Website, $source->type);
        Bus::assertDispatched(fn (CrawlKnowledgeSourceJob $job) => $job->sourceId === $source->id);
    }

    #[Test]
    public function ne_reexplorer_qu_une_source_de_type_site_web(): void
    {
        $sources = new InMemoryKnowledgeSourceRepository;
        $service = new KnowledgeSourceService($sources, new InMemoryKnowledgeDocumentRepository, new KnowledgeFileStorage('local'), new ImmediateTransactionManager, new InMemoryQuotaGuard);

        $upload = $sources->create(['workspace_id' => 'workspace-1', 'type' => KnowledgeSourceType::Upload, 'name' => 'Import']);

        $this->expectException(WebsiteSourceRequiredException::class);

        $service->recrawl($this->scope(), $upload->id);
    }

    #[Test]
    public function supprimer_une_source_efface_les_fichiers_de_ses_documents(): void
    {
        Storage::fake('local');

        $sources = new InMemoryKnowledgeSourceRepository;
        $documents = new InMemoryKnowledgeDocumentRepository;
        $storage = new KnowledgeFileStorage('local');
        $service = new KnowledgeSourceService($sources, $documents, $storage, new ImmediateTransactionManager, new InMemoryQuotaGuard);

        $source = $sources->create(['workspace_id' => 'workspace-1', 'type' => KnowledgeSourceType::Upload, 'name' => 'Import']);
        $diskPath = $storage->store('workspace-1', UploadedFile::fake()->create('a.txt', 5, 'text/plain'));
        $documents->create([
            'workspace_id' => 'workspace-1',
            'source_id' => $source->id,
            'type' => 'file',
            'title' => 'a.txt',
            'disk_path' => $diskPath,
            'status' => KnowledgeDocumentStatus::Indexed,
        ]);

        Storage::disk('local')->assertExists($diskPath);

        $service->delete($this->scope(), $source->id);

        Storage::disk('local')->assertMissing($diskPath);
    }
}
