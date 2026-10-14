<?php

namespace Tests\Unit\HelpCenter;

use App\Domains\HelpCenter\Enums\ArticleStatus;
use App\Domains\HelpCenter\Services\HelpCenterPublicService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryHelpCenterArticleRepository;
use Tests\Support\Fakes\InMemoryHelpCenterCategoryRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class HelpCenterPublicServiceTest extends TestCase
{
    #[Test]
    public function un_brouillon_n_apparait_jamais_dans_la_liste_publique(): void
    {
        $draft = ModelFactory::helpCenterArticle(['workspace_id' => 'workspace-1', 'locale' => 'fr', 'status' => ArticleStatus::Draft]);
        $published = ModelFactory::helpCenterArticle(['workspace_id' => 'workspace-1', 'locale' => 'fr', 'slug' => 'publie', 'status' => ArticleStatus::Published]);
        $service = new HelpCenterPublicService(
            new InMemoryHelpCenterCategoryRepository,
            new InMemoryHelpCenterArticleRepository($draft, $published),
        );

        $articles = $service->articles('workspace-1', 'fr', null);

        $this->assertCount(1, $articles);
        $this->assertSame($published->id, $articles->first()->id);
    }

    #[Test]
    public function un_brouillon_n_est_pas_accessible_par_son_slug(): void
    {
        $draft = ModelFactory::helpCenterArticle(['workspace_id' => 'workspace-1', 'locale' => 'fr', 'slug' => 'brouillon', 'status' => ArticleStatus::Draft]);
        $service = new HelpCenterPublicService(
            new InMemoryHelpCenterCategoryRepository,
            new InMemoryHelpCenterArticleRepository($draft),
        );

        $this->expectException(ModelNotFoundException::class);

        $service->article('workspace-1', 'fr', 'brouillon');
    }

    #[Test]
    public function un_article_publie_est_accessible_par_son_slug(): void
    {
        $published = ModelFactory::helpCenterArticle(['workspace_id' => 'workspace-1', 'locale' => 'fr', 'slug' => 'publie', 'status' => ArticleStatus::Published]);
        $service = new HelpCenterPublicService(
            new InMemoryHelpCenterCategoryRepository,
            new InMemoryHelpCenterArticleRepository($published),
        );

        $article = $service->article('workspace-1', 'fr', 'publie');

        $this->assertSame($published->id, $article->id);
    }
}
