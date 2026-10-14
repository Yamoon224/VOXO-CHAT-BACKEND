<?php

namespace App\Domains\HelpCenter\Repositories;

use App\Domains\HelpCenter\Contracts\HelpCenterArticleRepositoryContract;
use App\Models\HelpCenterArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentHelpCenterArticleRepository implements HelpCenterArticleRepositoryContract
{
    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        return HelpCenterArticle::query()
            ->with('category:id,name')
            ->where('workspace_id', $workspaceId)
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn (Builder $query, mixed $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['locale'] ?? null, fn (Builder $query, mixed $locale) => $query->where('locale', $locale))
            ->orderBy('position')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): HelpCenterArticle
    {
        return HelpCenterArticle::query()->with('category:id,name')->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function slugExists(string $workspaceId, string $locale, string $slug): bool
    {
        return HelpCenterArticle::query()
            ->where('workspace_id', $workspaceId)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->exists();
    }

    public function publishedForWorkspace(string $workspaceId, string $locale, ?string $categoryId = null): Collection
    {
        return HelpCenterArticle::query()
            ->with('category:id,name,slug')
            ->where('workspace_id', $workspaceId)
            ->where('locale', $locale)
            ->where('status', 'published')
            ->when($categoryId, fn (Builder $query, string $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('position')
            ->get();
    }

    public function findPublishedBySlug(string $workspaceId, string $locale, string $slug): ?HelpCenterArticle
    {
        return HelpCenterArticle::query()
            ->with('category:id,name,slug')
            ->where('workspace_id', $workspaceId)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();
    }

    public function create(array $attributes): HelpCenterArticle
    {
        return HelpCenterArticle::create($attributes);
    }

    public function update(HelpCenterArticle $article, array $attributes): HelpCenterArticle
    {
        $article->update($attributes);

        return $article;
    }

    public function delete(HelpCenterArticle $article): void
    {
        $article->delete();
    }
}
