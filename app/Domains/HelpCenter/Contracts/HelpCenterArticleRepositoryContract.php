<?php

namespace App\Domains\HelpCenter\Contracts;

use App\Models\HelpCenterArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface HelpCenterArticleRepositoryContract
{
    /**
     * @param  array<string, mixed>  $filters  `status`, `category_id`, `locale`
     * @return LengthAwarePaginator<int, HelpCenterArticle>
     */
    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): HelpCenterArticle;

    public function slugExists(string $workspaceId, string $locale, string $slug): bool;

    /**
     * Vue publique (section 2.10) : seuls les articles publiés, jamais les
     * brouillons — « articles privés » du cahier des charges.
     *
     * @return Collection<int, HelpCenterArticle>
     */
    public function publishedForWorkspace(string $workspaceId, string $locale, ?string $categoryId = null): Collection;

    public function findPublishedBySlug(string $workspaceId, string $locale, string $slug): ?HelpCenterArticle;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): HelpCenterArticle;

    /** @param  array<string, mixed>  $attributes */
    public function update(HelpCenterArticle $article, array $attributes): HelpCenterArticle;

    public function delete(HelpCenterArticle $article): void;
}
