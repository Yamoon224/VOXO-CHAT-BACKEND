<?php

namespace App\Domains\Contacts\Contracts;

use App\Models\ContactEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface ContactEventRepositoryContract
{
    /** @return Collection<int, ContactEvent> Les plus récents en premier. */
    public function forContact(string $contactId): Collection;

    /** @param  array<string, mixed>|null  $payload */
    public function record(string $workspaceId, string $contactId, string $type, ?array $payload = null, ?Carbon $occurredAt = null): ContactEvent;
}
