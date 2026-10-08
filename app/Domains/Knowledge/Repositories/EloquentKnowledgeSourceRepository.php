<?php

namespace App\Domains\Knowledge\Repositories;

use App\Domains\Knowledge\Contracts\KnowledgeSourceRepositoryContract;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Shared\Support\Sort;
use App\Models\KnowledgeSource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentKnowledgeSourceRepository implements KnowledgeSourceRepositoryContract
{
    /** @var array<string, string|array{0: string, 1: string}> */
    private const SORTABLE = ['created_at' => 'created_at'];

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return KnowledgeSource::query()
            ->where('workspace_id', $workspaceId)
            ->when($filters['type'] ?? null, fn (Builder $query, mixed $type) => $query->where('type', $type))
            ->tap(fn (Builder $query) => Sort::apply($query, $filters, self::SORTABLE, 'created_at', 'desc'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): KnowledgeSource
    {
        return KnowledgeSource::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function findOrFailById(string $id): KnowledgeSource
    {
        return KnowledgeSource::query()->findOrFail($id);
    }

    public function create(array $attributes): KnowledgeSource
    {
        return KnowledgeSource::create($attributes);
    }

    public function findManualSource(string $workspaceId): ?KnowledgeSource
    {
        return KnowledgeSource::query()
            ->where('workspace_id', $workspaceId)
            ->where('type', KnowledgeSourceType::Manual)
            ->first();
    }

    public function delete(KnowledgeSource $source): void
    {
        $source->delete();
    }

    public function markCrawled(KnowledgeSource $source): KnowledgeSource
    {
        $source->update(['last_crawled_at' => now()]);

        return $source;
    }

    public function dueForRecrawl(): Collection
    {
        return KnowledgeSource::query()
            ->where('type', KnowledgeSourceType::Website)
            ->where('recrawl_frequency', '!=', 'manual')
            ->get()
            ->filter(fn (KnowledgeSource $source) => $source->isDueForRecrawl())
            ->values();
    }
}
