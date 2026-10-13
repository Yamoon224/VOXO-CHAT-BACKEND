<?php

namespace App\Domains\Contacts\Repositories;

use App\Domains\Contacts\Contracts\ContactNoteRepositoryContract;
use App\Models\ContactNote;
use Illuminate\Support\Collection;

final class EloquentContactNoteRepository implements ContactNoteRepositoryContract
{
    /** @return Collection<int, ContactNote> */
    public function forContact(string $contactId): Collection
    {
        return ContactNote::query()->with('author')->where('contact_id', $contactId)->latest()->get();
    }

    public function create(array $attributes): ContactNote
    {
        return ContactNote::query()->create($attributes)->load('author');
    }
}
