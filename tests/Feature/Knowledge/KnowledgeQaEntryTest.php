<?php

namespace Tests\Feature\Knowledge;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeQaEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ajouter_une_entree_l_indexe_immediatement(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $response = $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/qa-entries', [
            'question' => 'Quels sont vos horaires ?',
            'answer' => 'Nous sommes ouverts de 9h à 18h, du lundi au vendredi.',
        ])->assertCreated();

        $this->assertSame('indexed', $response->json('data.status'));
        $this->assertGreaterThan(0, $response->json('data.chunk_count'));
    }

    #[Test]
    public function une_question_vide_est_refusee(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/qa-entries', [
            'question' => '',
            'answer' => 'Une réponse.',
        ])->assertStatus(422);
    }

    #[Test]
    public function modifier_une_entree_la_reindexe(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $created = $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/qa-entries', [
            'question' => 'Livrez-vous le dimanche ?',
            'answer' => 'Non.',
        ])->assertCreated();

        $updated = $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/knowledge/qa-entries/{$created->json('data.id')}", [
                'question' => 'Livrez-vous le week-end ?',
                'answer' => 'Seulement le samedi.',
            ])
            ->assertOk();

        $this->assertSame('Livrez-vous le week-end ?', $updated->json('data.question'));
        $this->assertSame('indexed', $updated->json('data.status'));
    }

    #[Test]
    public function supprimer_une_entree(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $created = $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/qa-entries', [
            'question' => 'Question à supprimer ?',
            'answer' => 'Réponse.',
        ])->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/knowledge/qa-entries/{$created->json('data.id')}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/knowledge/documents/{$created->json('data.id')}")
            ->assertStatus(404);
    }
}
