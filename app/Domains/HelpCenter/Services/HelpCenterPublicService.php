<?php

namespace App\Domains\HelpCenter\Services;

use App\Domains\HelpCenter\Contracts\HelpCenterArticleRepositoryContract;
use App\Domains\HelpCenter\Contracts\HelpCenterCategoryRepositoryContract;
use App\Models\HelpCenterArticle;
use App\Models\HelpCenterCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/** Pages publiques du centre d'aide (section 2.10) : jamais un brouillon. */
final class HelpCenterPublicService
{
    public function __construct(
        private readonly HelpCenterCategoryRepositoryContract $categories,
        private readonly HelpCenterArticleRepositoryContract $articles,
    ) {}

    /** @return Collection<int, HelpCenterCategory> */
    public function categories(string $workspaceId, string $locale): Collection
    {
        return $this->categories->forWorkspace($workspaceId, $locale);
    }

    /** @return Collection<int, HelpCenterArticle> */
    public function articles(string $workspaceId, string $locale, ?string $categoryId): Collection
    {
        return $this->articles->publishedForWorkspace($workspaceId, $locale, $categoryId);
    }

    public function article(string $workspaceId, string $locale, string $slug): HelpCenterArticle
    {
        return $this->articles->findPublishedBySlug($workspaceId, $locale, $slug) ?? throw new ModelNotFoundException;
    }
}
