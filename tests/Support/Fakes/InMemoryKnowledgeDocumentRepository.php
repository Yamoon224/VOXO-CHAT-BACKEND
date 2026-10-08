<?php

namespace Tests\Support\Fakes;

use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InMemoryKnowledgeDocumentRepository implements KnowledgeDocumentRepositoryContract
{
    /** @var array<string, KnowledgeDocument> */
    private array $documents = [];

    public function __construct(KnowledgeDocument ...$documents)
    {
        foreach ($documents as $document) {
            $this->documents[$document->id] = $document;
        }
    }

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $items = array_values(array_filter($this->documents, fn (KnowledgeDocument $d) => $d->workspace_id === $workspaceId));

        return new Paginator($items, count($items), $perPage, 1);
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): KnowledgeDocument
    {
        $document = $this->documents[$id] ?? null;

        if ($document === null || $document->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $document;
    }

    public function findOrFailById(string $id): KnowledgeDocument
    {
        return $this->documents[$id] ?? throw new ModelNotFoundException;
    }

    public function create(array $attributes): KnowledgeDocument
    {
        $document = ModelFactory::knowledgeDocument($attributes);

        return $this->documents[$document->id] = $document;
    }

    public function update(KnowledgeDocument $document, array $attributes): KnowledgeDocument
    {
        $document->setRawAttributes($attributes + $document->getAttributes(), true);

        return $document;
    }

    public function findByOriginUrl(string $sourceId, string $originUrl): ?KnowledgeDocument
    {
        foreach ($this->documents as $document) {
            if ($document->source_id === $sourceId && $document->origin_url === $originUrl) {
                return $document;
            }
        }

        return null;
    }

    public function delete(KnowledgeDocument $document): void
    {
        unset($this->documents[$document->id]);
    }

    public function forSource(string $sourceId): Collection
    {
        return (new Collection($this->documents))->filter(fn (KnowledgeDocument $d) => $d->source_id === $sourceId)->values();
    }
}
