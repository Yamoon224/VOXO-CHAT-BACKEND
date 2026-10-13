<?php

namespace App\Domains\Contacts\Contracts;

use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ContactRepositoryContract
{
    /**
     * @param  array<string, mixed>  $filters  `search`, `sort`, `direction`
     * @return LengthAwarePaginator<int, Contact>
     */
    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findInWorkspaceOrFail(string $workspaceId, string $contactId): Contact;

    public function findByEmail(string $workspaceId, string $email): ?Contact;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Contact;

    /** @param  array<string, mixed>  $attributes */
    public function update(Contact $contact, array $attributes): Contact;

    public function delete(Contact $contact): void;

    /** @return Collection<int, Contact> Tous les contacts de l'espace, pour l'export et les segments. */
    public function all(string $workspaceId): Collection;
}
