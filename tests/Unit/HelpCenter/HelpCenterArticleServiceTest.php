<?php

namespace Tests\Unit\HelpCenter;

use App\Domains\HelpCenter\Enums\ArticleStatus;
use App\Domains\HelpCenter\Services\HelpCenterArticleService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryHelpCenterArticleRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class HelpCenterArticleServiceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function attributes(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Comment réinitialiser mon mot de passe',
            'locale' => 'fr',
            'category_id' => null,
            'body' => 'Rendez-vous sur la page de connexion...',
            'status' => ArticleStatus::Draft,
            'meta_description' => null,
            'position' => 0,
        ];
    }

    #[Test]
    public function creer_un_article_derive_son_slug_du_titre(): void
    {
        $service = new HelpCenterArticleService(new InMemoryHelpCenterArticleRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $article = $service->create($scope, $this->attributes());

        $this->assertSame('comment-reinitialiser-mon-mot-de-passe', $article->slug);
        $this->assertNull($article->published_at);
    }

    #[Test]
    public function publier_a_la_creation_horodate_la_publication(): void
    {
        $service = new HelpCenterArticleService(new InMemoryHelpCenterArticleRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $article = $service->create($scope, $this->attributes(['status' => ArticleStatus::Published]));

        $this->assertNotNull($article->published_at);
    }

    #[Test]
    public function passer_un_brouillon_en_publie_horodate_la_publication(): void
    {
        $existing = ModelFactory::helpCenterArticle([
            'workspace_id' => 'workspace-1',
            'title' => 'Titre',
            'slug' => 'titre',
            'status' => ArticleStatus::Draft,
            'published_at' => null,
        ]);
        $service = new HelpCenterArticleService(new InMemoryHelpCenterArticleRepository($existing));
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $updated = $service->update($scope, $existing->id, $this->attributes(['title' => 'Titre', 'status' => ArticleStatus::Published]));

        $this->assertNotNull($updated->published_at);
    }

    #[Test]
    public function repasser_un_article_publie_en_brouillon_efface_la_publication(): void
    {
        $existing = ModelFactory::helpCenterArticle([
            'workspace_id' => 'workspace-1',
            'title' => 'Titre',
            'slug' => 'titre',
            'status' => ArticleStatus::Published,
            'published_at' => now(),
        ]);
        $service = new HelpCenterArticleService(new InMemoryHelpCenterArticleRepository($existing));
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $updated = $service->update($scope, $existing->id, $this->attributes(['title' => 'Titre', 'status' => ArticleStatus::Draft]));

        $this->assertNull($updated->published_at);
    }

    #[Test]
    public function modifier_le_titre_change_le_slug(): void
    {
        $existing = ModelFactory::helpCenterArticle([
            'workspace_id' => 'workspace-1',
            'title' => 'Ancien titre',
            'slug' => 'ancien-titre',
            'status' => ArticleStatus::Draft,
        ]);
        $service = new HelpCenterArticleService(new InMemoryHelpCenterArticleRepository($existing));
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $updated = $service->update($scope, $existing->id, $this->attributes(['title' => 'Nouveau titre', 'status' => ArticleStatus::Draft]));

        $this->assertSame('nouveau-titre', $updated->slug);
    }
}
