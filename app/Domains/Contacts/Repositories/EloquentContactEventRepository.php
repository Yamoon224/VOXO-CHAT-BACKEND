<?php

namespace App\Domains\Contacts\Repositories;

use App\Domains\Contacts\Contracts\ContactEventRepositoryContract;
use App\Models\ContactEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class EloquentContactEventRepository implements ContactEventRepositoryContract
{
    /** @return Collection<int, ContactEvent> */
    public function forContact(string $contactId): Collection
    {
        return ContactEvent::query()->where('contact_id', $contactId)->orderByDesc('occurred_at')->get();
    }

    public function record(string $workspaceId, string $contactId, string $type, ?array $payload = null, ?Carbon $occurredAt = null): ContactEvent
    {
        return ContactEvent::query()->create([
            'workspace_id' => $workspaceId,
            'contact_id' => $contactId,
            'type' => $type,
            'payload' => $payload,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
