<?php

namespace App\Domains\HelpCenter\Contracts;

use App\Models\HelpCenterCategory;
use Illuminate\Support\Collection;

interface HelpCenterCategoryRepositoryContract
{
    /** @return Collection<int, HelpCenterCategory> Triées par position, pour l'ordre d'affichage public. */
    public function forWorkspace(string $workspaceId, ?string $locale = null): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): HelpCenterCategory;

    public function slugExists(string $workspaceId, string $locale, string $slug): bool;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): HelpCenterCategory;

    /** @param  array<string, mixed>  $attributes */
    public function update(HelpCenterCategory $category, array $attributes): HelpCenterCategory;

    public function delete(HelpCenterCategory $category): void;
}
