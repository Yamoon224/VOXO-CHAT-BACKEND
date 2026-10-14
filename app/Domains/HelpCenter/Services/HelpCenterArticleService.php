<?php

namespace App\Domains\HelpCenter\Services;

use App\Domains\HelpCenter\Contracts\HelpCenterArticleRepositoryContract;
use App\Domains\HelpCenter\Enums\ArticleStatus;
use App\Domains\HelpCenter\Support\HelpCenterSlug;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\HelpCenterArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HelpCenterArticleService
{
    public function __construct(private readonly HelpCenterArticleRepositoryContract $articles) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, HelpCenterArticle>
     */
    public function list(WorkspaceScope $scope, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->articles->paginate($scope->workspaceId, $filters, $perPage);
    }

    public function get(WorkspaceScope $scope, string $articleId): HelpCenterArticle
    {
        return $this->articles->findInWorkspaceOrFail($scope->workspaceId, $articleId);
    }

    /** @param  array{title: string, locale: string, category_id: ?string, body: string, status: ArticleStatus, meta_description: ?string, position: int}  $attributes */
    public function create(WorkspaceScope $scope, array $attributes): HelpCenterArticle
    {
        return $this->articles->create([
            'workspace_id' => $scope->workspaceId,
            'category_id' => $attributes['category_id'],
            'title' => $attributes['title'],
            'slug' => $this->uniqueSlug($scope->workspaceId, $attributes['locale'], $attributes['title']),
            'locale' => $attributes['locale'],
            'body' => $attributes['body'],
            'status' => $attributes['status'],
            'meta_description' => $attributes['meta_description'],
            'position' => $attributes['position'],
            'published_at' => $attributes['status'] === ArticleStatus::Published ? now() : null,
        ]);
    }

    /** @param  array{title: string, category_id: ?string, body: string, status: ArticleStatus, meta_description: ?string, position: int}  $attributes */
    public function update(WorkspaceScope $scope, string $articleId, array $attributes): HelpCenterArticle
    {
        $article = $this->articles->findInWorkspaceOrFail($scope->workspaceId, $articleId);

        $slug = $attributes['title'] !== $article->title
            ? $this->uniqueSlug($scope->workspaceId, $article->locale, $attributes['title'])
            : $article->slug;

        $becomesPublished = $attributes['status'] === ArticleStatus::Published && $article->status !== ArticleStatus::Published;
        $becomesDraft = $attributes['status'] === ArticleStatus::Draft && $article->status !== ArticleStatus::Draft;

        return $this->articles->update($article, [
            'category_id' => $attributes['category_id'],
            'title' => $attributes['title'],
            'slug' => $slug,
            'body' => $attributes['body'],
            'status' => $attributes['status'],
            'meta_description' => $attributes['meta_description'],
            'position' => $attributes['position'],
            'published_at' => match (true) {
                $becomesPublished => now(),
                $becomesDraft => null,
                default => $article->published_at,
            },
        ]);
    }

    public function delete(WorkspaceScope $scope, string $articleId): void
    {
        $this->articles->delete($this->articles->findInWorkspaceOrFail($scope->workspaceId, $articleId));
    }

    private function uniqueSlug(string $workspaceId, string $locale, string $title): string
    {
        $base = HelpCenterSlug::base($title);
        $slug = $base;
        $attempt = 0;

        while ($this->articles->slugExists($workspaceId, $locale, $slug)) {
            $slug = HelpCenterSlug::withSuffix($base, $attempt++);
        }

        return $slug;
    }
}
