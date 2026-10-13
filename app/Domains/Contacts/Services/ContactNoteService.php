<?php

namespace App\Domains\Contacts\Services;

use App\Domains\Contacts\Contracts\ContactEventRepositoryContract;
use App\Domains\Contacts\Contracts\ContactNoteRepositoryContract;
use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Models\ContactNote;
use Illuminate\Support\Collection;

final class ContactNoteService
{
    public function __construct(
        private readonly ContactRepositoryContract $contacts,
        private readonly ContactNoteRepositoryContract $notes,
        private readonly ContactEventRepositoryContract $events,
    ) {}

    /** @return Collection<int, ContactNote> */
    public function list(string $workspaceId, string $contactId): Collection
    {
        $this->contacts->findInWorkspaceOrFail($workspaceId, $contactId);

        return $this->notes->forContact($contactId);
    }

    public function create(string $workspaceId, string $contactId, string $body, ?string $authorUserId): ContactNote
    {
        $this->contacts->findInWorkspaceOrFail($workspaceId, $contactId);

        $note = $this->notes->create([
            'workspace_id' => $workspaceId,
            'contact_id' => $contactId,
            'author_user_id' => $authorUserId,
            'body' => $body,
        ]);

        $this->events->record($workspaceId, $contactId, 'note_added', ['note_id' => $note->id]);

        return $note;
    }
}
