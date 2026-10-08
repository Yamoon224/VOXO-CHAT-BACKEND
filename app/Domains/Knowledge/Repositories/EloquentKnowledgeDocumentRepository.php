<?php

namespace App\Domains\Knowledge\Repositories;

use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Shared\Support\Sort;
use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentKnowledgeDocumentRepository implements KnowledgeDocumentRepositoryContract
{
    /** @var array<string, string|array{0: string, 1: string}> */
    private const SORTABLE = ['created_at' => 'created_at'];

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return KnowledgeDocument::query()
            ->where('workspace_id', $workspaceId)
            ->when($filters['source_id'] ?? null, fn (Builder $query, mixed $sourceId) => $query->where('source_id', $sourceId))
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $query, mixed $type) => $query->where('type', $type))
            ->tap(fn (Builder $query) => Sort::apply($query, $filters, self::SORTABLE, 'created_at', 'desc'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): KnowledgeDocument
    {
        return KnowledgeDocument::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function findOrFailById(string $id): KnowledgeDocument
    {
        return KnowledgeDocument::query()->findOrFail($id);
    }

    public function create(array $attributes): KnowledgeDocument
    {
        return KnowledgeDocument::create($attributes);
    }

    public function update(KnowledgeDocument $document, array $attributes): KnowledgeDocument
    {
        $document->update($attributes);

        return $document;
    }

    public function findByOriginUrl(string $sourceId, string $originUrl): ?KnowledgeDocument
    {
        return KnowledgeDocument::query()
            ->where('source_id', $sourceId)
            ->where('origin_url', $originUrl)
            ->first();
    }

    public function delete(KnowledgeDocument $document): void
    {
        $document->delete();
    }

    public function forSource(string $sourceId): Collection
    {
        return KnowledgeDocument::query()->where('source_id', $sourceId)->get();
    }
}
