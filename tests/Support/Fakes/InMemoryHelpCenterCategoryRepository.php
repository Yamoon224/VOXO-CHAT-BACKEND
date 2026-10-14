<?php

namespace Tests\Support\Fakes;

use App\Domains\HelpCenter\Contracts\HelpCenterCategoryRepositoryContract;
use App\Models\HelpCenterCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemoryHelpCenterCategoryRepository implements HelpCenterCategoryRepositoryContract
{
    /** @var array<string, HelpCenterCategory> */
    private array $categories = [];

    public function __construct(HelpCenterCategory ...$categories)
    {
        foreach ($categories as $category) {
            $this->categories[$category->id] = $category;
        }
    }

    public function forWorkspace(string $workspaceId, ?string $locale = null): Collection
    {
        return (new Collection($this->categories))
            ->filter(fn (HelpCenterCategory $category) => $category->workspace_id === $workspaceId
                && ($locale === null || $category->locale === $locale))
            ->sortBy(fn (HelpCenterCategory $category) => $category->position)
            ->values();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): HelpCenterCategory
    {
        $category = $this->categories[$id] ?? null;

        if ($category === null || $category->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $category;
    }

    public function slugExists(string $workspaceId, string $locale, string $slug): bool
    {
        foreach ($this->categories as $category) {
            if ($category->workspace_id === $workspaceId && $category->locale === $locale && $category->slug === $slug) {
                return true;
            }
        }

        return false;
    }

    public function create(array $attributes): HelpCenterCategory
    {
        $category = ModelFactory::helpCenterCategory($attributes);

        return $this->categories[$category->id] = $category;
    }

    public function update(HelpCenterCategory $category, array $attributes): HelpCenterCategory
    {
        $category->setRawAttributes($attributes + $category->getAttributes(), true);

        return $category;
    }

    public function delete(HelpCenterCategory $category): void
    {
        unset($this->categories[$category->id]);
    }
}
