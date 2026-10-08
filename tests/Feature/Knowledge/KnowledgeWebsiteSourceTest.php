<?php

namespace Tests\Feature\Knowledge;

use App\Domains\Knowledge\DTOs\CrawledPage;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\KnowledgeSource;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeWebsiteSourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function explorer_un_site_cree_un_document_indexe_par_page(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->webCrawler()->queue([
            new CrawledPage('https://example.test/', 'Accueil', 'Bienvenue sur notre site de support.'),
            new CrawledPage('https://example.test/faq', 'FAQ', 'Les questions fréquentes sur nos services.'),
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/websites', [
            'url' => 'https://example.test/',
            'sitemap_url' => 'https://example.test/sitemap.xml',
            'recrawl_frequency' => 'daily',
        ])->assertCreated();

        $sourceId = $response->json('data.id');

        $documents = $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/knowledge/documents?source_id={$sourceId}")
            ->assertOk();

        $this->assertCount(2, $documents->json('data'));
        $this->assertSame('indexed', $documents->json('data.0.status'));

        $this->assertNotNull(KnowledgeSource::query()->findOrFail($sourceId)->last_crawled_at);
    }

    #[Test]
    public function reexplorer_une_source_qui_n_est_pas_un_site_web_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->webCrawler()->queue([]);

        $upload = $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/uploads', [
            'name' => 'Import',
            'files' => [UploadedFile::fake()->createWithContent('a.txt', 'Contenu.')],
        ])->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/knowledge/sources/{$upload->json('data.id')}/recrawl")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'website_source_required');
    }
}
