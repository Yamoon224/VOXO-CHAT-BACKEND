<?php

namespace App\Domains\Contacts\Contracts;

use App\Models\ContactNote;
use Illuminate\Support\Collection;

interface ContactNoteRepositoryContract
{
    /** @return Collection<int, ContactNote> Les plus récentes en premier. */
    public function forContact(string $contactId): Collection;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): ContactNote;
}
