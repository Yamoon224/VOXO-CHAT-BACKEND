<?php

namespace App\Domains\Contacts\Repositories;

use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Domains\Shared\Support\Sort;
use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentContactRepository implements ContactRepositoryContract
{
    /** @var array<string, string> */
    private const SORTABLE = ['name' => 'name', 'created_at' => 'created_at'];

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        return Contact::query()
            ->where('workspace_id', $workspaceId)
            ->when(is_string($search) && $search !== '', fn (Builder $query) => $query->where(
                fn (Builder $match) => $match->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"),
            ))
            ->tap(fn (Builder $query) => Sort::apply($query, $filters, self::SORTABLE, 'created_at', 'desc'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $contactId): Contact
    {
        return Contact::query()->where('workspace_id', $workspaceId)->findOrFail($contactId);
    }

    public function findByEmail(string $workspaceId, string $email): ?Contact
    {
        return Contact::query()->where('workspace_id', $workspaceId)->where('email', $email)->first();
    }

    public function create(array $attributes): Contact
    {
        return Contact::query()->create($attributes);
    }

    public function update(Contact $contact, array $attributes): Contact
    {
        $contact->update($attributes);

        return $contact;
    }

    public function delete(Contact $contact): void
    {
        $contact->delete();
    }

    /** @return Collection<int, Contact> */
    public function all(string $workspaceId): Collection
    {
        return Contact::query()->where('workspace_id', $workspaceId)->orderBy('created_at')->get();
    }
}
