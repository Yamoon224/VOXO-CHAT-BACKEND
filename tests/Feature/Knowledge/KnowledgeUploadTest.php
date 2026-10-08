<?php

namespace Tests\Feature\Knowledge;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function importer_des_fichiers_cree_une_source_et_indexe_chaque_document(): void
    {
        Storage::fake('local');

        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $response = $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/uploads', [
            'name' => 'Documentation produit',
            'files' => [
                UploadedFile::fake()->createWithContent('guide.txt', 'Le support répond en moins de deux heures.'),
                UploadedFile::fake()->createWithContent('faq.txt', 'Les remboursements sont traités en 48 heures.'),
            ],
        ])->assertCreated();

        $sourceId = $response->json('data.id');
        $this->assertSame('upload', $response->json('data.type'));

        $documents = $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/knowledge/documents?source_id={$sourceId}")
            ->assertOk();

        $this->assertCount(2, $documents->json('data'));

        foreach ($documents->json('data') as $document) {
            $this->assertSame('indexed', $document['status']);
            $this->assertGreaterThan(0, $document['chunk_count']);
        }
    }

    #[Test]
    public function un_type_de_fichier_non_pris_en_charge_est_refuse(): void
    {
        Storage::fake('local');

        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/uploads', [
            'name' => 'Import',
            'files' => [UploadedFile::fake()->create('virus.exe', 10)],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed');
    }

    #[Test]
    public function un_lecteur_ne_peut_pas_importer_de_fichiers(): void
    {
        Storage::fake('local');

        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($viewer, $workspace)->postJson('/api/v1/workspace/knowledge/uploads', [
            'name' => 'Import',
            'files' => [UploadedFile::fake()->createWithContent('a.txt', 'Contenu.')],
        ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');
    }
}
