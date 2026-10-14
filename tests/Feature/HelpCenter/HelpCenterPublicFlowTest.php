<?php

namespace Tests\Feature\HelpCenter;

use App\Models\HelpCenterArticle;
use App\Models\HelpCenterCategory;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpCenterPublicFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function les_categories_publiques_sont_lisibles_sans_authentification(): void
    {
        $workspace = Workspace::factory()->create();
        HelpCenterCategory::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Facturation', 'locale' => 'fr']);

        $this->getJson("/api/v1/public/help-center/{$workspace->id}/categories")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Facturation');
    }

    #[Test]
    public function seuls_les_articles_publies_apparaissent_dans_la_liste_publique(): void
    {
        $workspace = Workspace::factory()->create();
        HelpCenterArticle::factory()->create(['workspace_id' => $workspace->id, 'locale' => 'fr', 'title' => 'Brouillon']);
        HelpCenterArticle::factory()->published()->create(['workspace_id' => $workspace->id, 'locale' => 'fr', 'title' => 'Publié']);

        $response = $this->getJson("/api/v1/public/help-center/{$workspace->id}/articles")->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Publié', $response->json('data.0.title'));
    }

    #[Test]
    public function un_article_publie_est_accessible_par_son_slug(): void
    {
        $workspace = Workspace::factory()->create();
        $article = HelpCenterArticle::factory()->published()->create([
            'workspace_id' => $workspace->id,
            'locale' => 'fr',
            'title' => 'Comment réinitialiser mon mot de passe',
            'slug' => 'comment-reinitialiser-mon-mot-de-passe',
        ]);

        $this->getJson("/api/v1/public/help-center/{$workspace->id}/articles/{$article->slug}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Comment réinitialiser mon mot de passe');
    }

    #[Test]
    public function un_brouillon_repond_404_meme_avec_le_bon_slug(): void
    {
        $workspace = Workspace::factory()->create();
        $article = HelpCenterArticle::factory()->create(['workspace_id' => $workspace->id, 'locale' => 'fr', 'slug' => 'brouillon']);

        $this->getJson("/api/v1/public/help-center/{$workspace->id}/articles/{$article->slug}")
            ->assertStatus(404);
    }

    #[Test]
    public function les_articles_d_un_autre_espace_ne_fuient_pas(): void
    {
        $workspace = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        HelpCenterArticle::factory()->published()->create(['workspace_id' => $other->id, 'locale' => 'fr', 'slug' => 'autre-espace']);

        $this->getJson("/api/v1/public/help-center/{$workspace->id}/articles/autre-espace")
            ->assertStatus(404);
    }
}
