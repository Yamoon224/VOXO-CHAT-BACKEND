<?php

namespace Tests\Feature\Knowledge;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Un espace de travail ne lit ni ne modifie les documents ou les résultats de
 * recherche d'un autre — recherche sémantique comprise (cahier des charges,
 * section 4.5).
 */
class KnowledgeIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function les_documents_d_un_espace_n_apparaissent_pas_dans_un_autre(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $adminA = $this->memberOf($workspaceA, WorkspaceRole::Admin);

        $sourceB = KnowledgeSource::factory()->for($workspaceB)->create();
        KnowledgeDocument::factory()->for($workspaceB)->for($sourceB, 'source')->indexed()->create();

        $response = $this->actingInWorkspace($adminA, $workspaceA)
            ->getJson('/api/v1/workspace/knowledge/documents')
            ->assertOk();

        $this->assertSame([], $response->json('data'));
    }

    #[Test]
    public function un_identifiant_de_document_d_un_autre_espace_est_introuvable(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $adminA = $this->memberOf($workspaceA, WorkspaceRole::Admin);
        $this->memberOf($workspaceB, WorkspaceRole::Admin);

        $sourceB = KnowledgeSource::factory()->for($workspaceB)->create();
        $documentB = KnowledgeDocument::factory()->for($workspaceB)->for($sourceB, 'source')->indexed()->create();

        $this->actingInWorkspace($adminA, $workspaceA)
            ->getJson("/api/v1/workspace/knowledge/documents/{$documentB->id}")
            ->assertStatus(404);
    }

    #[Test]
    public function la_recherche_ne_renvoie_jamais_les_passages_d_un_autre_espace(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $adminA = $this->memberOf($workspaceA, WorkspaceRole::Admin);
        $adminB = $this->memberOf($workspaceB, WorkspaceRole::Admin);

        $this->actingInWorkspace($adminB, $workspaceB)->postJson('/api/v1/workspace/knowledge/qa-entries', [
            'question' => 'Information confidentielle de B ?',
            'answer' => 'Donnée strictement interne à cet espace.',
        ])->assertCreated();

        $response = $this->actingInWorkspace($adminA, $workspaceA)
            ->postJson('/api/v1/workspace/knowledge/search', ['query' => 'information confidentielle'])
            ->assertOk();

        $this->assertSame([], $response->json('data'));
    }
}
