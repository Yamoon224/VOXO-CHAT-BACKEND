<?php

namespace Tests\Feature\HelpCenter;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\HelpCenterCategory;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpCenterManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_cree_une_categorie_avec_un_slug_derive(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/help-center/categories', ['name' => 'Facturation et paiements'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Facturation et paiements')
            ->assertJsonPath('data.slug', 'facturation-et-paiements');
    }

    #[Test]
    public function un_viewer_ne_peut_pas_creer_de_categorie_mais_peut_les_consulter(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($viewer, $workspace)
            ->postJson('/api/v1/workspace/help-center/categories', ['name' => 'Facturation'])
            ->assertStatus(403);

        $this->actingInWorkspace($viewer, $workspace)
            ->getJson('/api/v1/workspace/help-center/categories')
            ->assertOk();
    }

    #[Test]
    public function supprimer_une_categorie_ne_supprime_pas_ses_articles(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $category = HelpCenterCategory::factory()->create(['workspace_id' => $workspace->id]);

        $article = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/help-center/articles', [
                'title' => 'Comment réinitialiser mon mot de passe',
                'category_id' => $category->id,
                'body' => 'Rendez-vous sur la page de connexion...',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/help-center/categories/{$category->id}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/help-center/articles/{$article}")
            ->assertOk()
            ->assertJsonPath('data.category', null);
    }

    #[Test]
    public function un_administrateur_cree_un_article_en_brouillon_par_defaut(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/help-center/articles', [
                'title' => 'Comment réinitialiser mon mot de passe',
                'body' => 'Rendez-vous sur la page de connexion...',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.slug', 'comment-reinitialiser-mon-mot-de-passe')
            ->assertJsonPath('data.published_at', null);
    }

    #[Test]
    public function publier_un_article_horodate_sa_publication(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $created = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/help-center/articles', [
                'title' => 'Titre',
                'body' => 'Corps de l\'article.',
            ])
            ->assertCreated();

        $articleId = $created->json('data.id');

        $response = $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/help-center/articles/{$articleId}", [
                'title' => 'Titre',
                'body' => 'Corps de l\'article.',
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertNotNull($response->json('data.published_at'));
    }

    #[Test]
    public function la_liste_se_filtre_par_statut(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/help-center/articles', [
            'title' => 'Brouillon',
            'body' => 'Corps.',
        ]);
        $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/help-center/articles', [
            'title' => 'Publié',
            'body' => 'Corps.',
            'status' => 'published',
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/help-center/articles?status=published')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Publié', $response->json('data.0.title'));
    }
}
