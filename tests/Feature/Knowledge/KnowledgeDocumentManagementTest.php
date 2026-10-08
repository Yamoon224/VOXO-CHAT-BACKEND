<?php

namespace Tests\Feature\Knowledge;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_document_en_echec_peut_etre_redeclenche(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $source = KnowledgeSource::factory()->for($workspace)->create();
        $document = KnowledgeDocument::factory()->for($workspace)->for($source, 'source')->failed()->create();

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/knowledge/documents/{$document->id}/retry")
            ->assertOk()
            ->assertJsonPath('data.status_message', null);
    }

    #[Test]
    public function un_document_qui_n_est_pas_en_echec_refuse_le_redeclenchement(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $source = KnowledgeSource::factory()->for($workspace)->create();
        $document = KnowledgeDocument::factory()->for($workspace)->for($source, 'source')->indexed()->create();

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/knowledge/documents/{$document->id}/retry")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'document_not_retryable');
    }

    #[Test]
    public function la_liste_des_documents_se_filtre_par_statut(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $source = KnowledgeSource::factory()->for($workspace)->create();
        KnowledgeDocument::factory()->for($workspace)->for($source, 'source')->indexed()->create();
        KnowledgeDocument::factory()->for($workspace)->for($source, 'source')->failed()->create();

        $response = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/knowledge/documents?status=failed')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('failed', $response->json('data.0.status'));
    }
}
