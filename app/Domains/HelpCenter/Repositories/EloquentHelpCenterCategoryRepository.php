<?php

namespace App\Domains\HelpCenter\Repositories;

use App\Domains\HelpCenter\Contracts\HelpCenterCategoryRepositoryContract;
use App\Models\HelpCenterCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentHelpCenterCategoryRepository implements HelpCenterCategoryRepositoryContract
{
    public function forWorkspace(string $workspaceId, ?string $locale = null): Collection
    {
        return HelpCenterCategory::query()
            ->where('workspace_id', $workspaceId)
            ->when($locale, fn (Builder $query, string $locale) => $query->where('locale', $locale))
            ->orderBy('position')
            ->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): HelpCenterCategory
    {
        return HelpCenterCategory::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function slugExists(string $workspaceId, string $locale, string $slug): bool
    {
        return HelpCenterCategory::query()
            ->where('workspace_id', $workspaceId)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->exists();
    }

    public function create(array $attributes): HelpCenterCategory
    {
        return HelpCenterCategory::create($attributes);
    }

    public function update(HelpCenterCategory $category, array $attributes): HelpCenterCategory
    {
        $category->update($attributes);

        return $category;
    }

    public function delete(HelpCenterCategory $category): void
    {
        $category->delete();
    }
}
