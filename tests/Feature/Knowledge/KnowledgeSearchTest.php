<?php

namespace Tests\Feature\Knowledge;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_recherche_renvoie_le_passage_le_plus_proche_du_vocabulaire_de_la_requete(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/qa-entries', [
            'question' => 'Quel est le tarif mensuel ?',
            'answer' => 'Le tarif mensuel est de 29 euros par mois.',
        ])->assertCreated();

        $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/qa-entries', [
            'question' => 'Quels sont les délais de livraison ?',
            'answer' => 'La livraison prend deux à cinq jours ouvrés.',
        ])->assertCreated();

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/knowledge/search', ['query' => 'tarif mensuel', 'limit' => 1])
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('tarif', mb_strtolower($response->json('data.0.chunk_content')));
    }

    #[Test]
    public function une_requete_trop_courte_est_refusee(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/knowledge/search', ['query' => 'a'])
            ->assertStatus(422);
    }
}
