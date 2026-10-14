<?php

namespace App\Domains\HelpCenter\Services;

use App\Domains\HelpCenter\Contracts\HelpCenterCategoryRepositoryContract;
use App\Domains\HelpCenter\Support\HelpCenterSlug;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\HelpCenterCategory;
use Illuminate\Support\Collection;

final class HelpCenterCategoryService
{
    public function __construct(private readonly HelpCenterCategoryRepositoryContract $categories) {}

    /** @return Collection<int, HelpCenterCategory> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->categories->forWorkspace($scope->workspaceId);
    }

    public function create(WorkspaceScope $scope, string $name, string $locale, int $position): HelpCenterCategory
    {
        return $this->categories->create([
            'workspace_id' => $scope->workspaceId,
            'name' => $name,
            'slug' => $this->uniqueSlug($scope->workspaceId, $locale, $name),
            'locale' => $locale,
            'position' => $position,
        ]);
    }

    public function update(WorkspaceScope $scope, string $categoryId, string $name, int $position): HelpCenterCategory
    {
        $category = $this->categories->findInWorkspaceOrFail($scope->workspaceId, $categoryId);

        $attributes = ['name' => $name, 'position' => $position];

        if ($name !== $category->name) {
            $attributes['slug'] = $this->uniqueSlug($scope->workspaceId, $category->locale, $name);
        }

        return $this->categories->update($category, $attributes);
    }

    public function delete(WorkspaceScope $scope, string $categoryId): void
    {
        $this->categories->delete($this->categories->findInWorkspaceOrFail($scope->workspaceId, $categoryId));
    }

    private function uniqueSlug(string $workspaceId, string $locale, string $name): string
    {
        $base = HelpCenterSlug::base($name);
        $slug = $base;
        $attempt = 0;

        while ($this->categories->slugExists($workspaceId, $locale, $slug)) {
            $slug = HelpCenterSlug::withSuffix($base, $attempt++);
        }

        return $slug;
    }
}
