<?php

namespace Tests\Support\Fakes;

use App\Domains\HelpCenter\Contracts\HelpCenterArticleRepositoryContract;
use App\Models\HelpCenterArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InMemoryHelpCenterArticleRepository implements HelpCenterArticleRepositoryContract
{
    /** @var array<string, HelpCenterArticle> */
    private array $articles = [];

    public function __construct(HelpCenterArticle ...$articles)
    {
        foreach ($articles as $article) {
            $this->articles[$article->id] = $article;
        }
    }

    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        $items = array_values(array_filter(
            $this->articles,
            fn (HelpCenterArticle $article) => $article->workspace_id === $workspaceId
                && (! isset($filters['status']) || $article->status->value === $filters['status'])
                && (! isset($filters['category_id']) || $article->category_id === $filters['category_id'])
                && (! isset($filters['locale']) || $article->locale === $filters['locale']),
        ));

        return new Paginator($items, count($items), $perPage, 1);
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): HelpCenterArticle
    {
        $article = $this->articles[$id] ?? null;

        if ($article === null || $article->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $article;
    }

    public function slugExists(string $workspaceId, string $locale, string $slug): bool
    {
        foreach ($this->articles as $article) {
            if ($article->workspace_id === $workspaceId && $article->locale === $locale && $article->slug === $slug) {
                return true;
            }
        }

        return false;
    }

    public function publishedForWorkspace(string $workspaceId, string $locale, ?string $categoryId = null): Collection
    {
        return (new Collection($this->articles))
            ->filter(fn (HelpCenterArticle $article) => $article->workspace_id === $workspaceId
                && $article->locale === $locale
                && $article->status->value === 'published'
                && ($categoryId === null || $article->category_id === $categoryId))
            ->sortBy(fn (HelpCenterArticle $article) => $article->position)
            ->values();
    }

    public function findPublishedBySlug(string $workspaceId, string $locale, string $slug): ?HelpCenterArticle
    {
        foreach ($this->articles as $article) {
            if ($article->workspace_id === $workspaceId
                && $article->locale === $locale
                && $article->slug === $slug
                && $article->status->value === 'published') {
                return $article;
            }
        }

        return null;
    }

    public function create(array $attributes): HelpCenterArticle
    {
        $article = ModelFactory::helpCenterArticle($attributes);

        return $this->articles[$article->id] = $article;
    }

    public function update(HelpCenterArticle $article, array $attributes): HelpCenterArticle
    {
        $article->setRawAttributes($attributes + $article->getAttributes(), true);

        return $article;
    }

    public function delete(HelpCenterArticle $article): void
    {
        unset($this->articles[$article->id]);
    }
}
