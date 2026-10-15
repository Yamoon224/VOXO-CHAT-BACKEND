<?php

namespace Tests\Support\Fakes;

use App\Domains\Contacts\Contracts\ContactEventRepositoryContract;
use App\Models\ContactEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class InMemoryContactEventRepository implements ContactEventRepositoryContract
{
    /** @var list<ContactEvent> */
    private array $events = [];

    public function forContact(string $contactId): Collection
    {
        return (new Collection($this->events))
            ->filter(fn (ContactEvent $event) => $event->contact_id === $contactId)
            ->sortByDesc(fn (ContactEvent $event) => $event->occurred_at)
            ->values();
    }

    public function record(string $workspaceId, string $contactId, string $type, ?array $payload = null, ?Carbon $occurredAt = null): ContactEvent
    {
        $event = new ContactEvent;
        $event->setRawAttributes([
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspaceId,
            'contact_id' => $contactId,
            'type' => $type,
            'payload' => $payload === null ? null : json_encode($payload),
            'occurred_at' => $occurredAt ?? now(),
        ], true);

        return $this->events[] = $event;
    }
}
