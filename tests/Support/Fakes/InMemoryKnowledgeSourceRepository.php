<?php

namespace Tests\Support\Fakes;

use App\Domains\Knowledge\Contracts\KnowledgeSourceRepositoryContract;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Models\KnowledgeSource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InMemoryKnowledgeSourceRepository implements KnowledgeSourceRepositoryContract
{
    /** @var array<string, KnowledgeSource> */
    private array $sources = [];

    public function __construct(KnowledgeSource ...$sources)
    {
        foreach ($sources as $source) {
            $this->sources[$source->id] = $source;
        }
    }

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $items = array_values(array_filter($this->sources, fn (KnowledgeSource $s) => $s->workspace_id === $workspaceId));

        return new Paginator($items, count($items), $perPage, 1);
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): KnowledgeSource
    {
        $source = $this->sources[$id] ?? null;

        if ($source === null || $source->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $source;
    }

    public function findOrFailById(string $id): KnowledgeSource
    {
        return $this->sources[$id] ?? throw new ModelNotFoundException;
    }

    public function create(array $attributes): KnowledgeSource
    {
        $source = ModelFactory::knowledgeSource($attributes);

        return $this->sources[$source->id] = $source;
    }

    public function findManualSource(string $workspaceId): ?KnowledgeSource
    {
        foreach ($this->sources as $source) {
            if ($source->workspace_id === $workspaceId && $source->type === KnowledgeSourceType::Manual) {
                return $source;
            }
        }

        return null;
    }

    public function delete(KnowledgeSource $source): void
    {
        unset($this->sources[$source->id]);
    }

    public function markCrawled(KnowledgeSource $source): KnowledgeSource
    {
        $source->setRawAttributes(['last_crawled_at' => now()] + $source->getAttributes(), true);

        return $source;
    }

    public function dueForRecrawl(): Collection
    {
        return (new Collection($this->sources))->filter(fn (KnowledgeSource $s) => $s->isDueForRecrawl())->values();
    }
}
