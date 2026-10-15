<?php

namespace App\Domains\Contacts\Services;

use App\Domains\Contacts\Contracts\ContactEventRepositoryContract;
use App\Domains\Contacts\Contracts\ContactLinkContract;
use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Domains\Contacts\Exceptions\ContactEmailTakenException;
use App\Models\Contact;
use App\Models\ContactEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * CRUD du profil client, et implémente `ContactLinkContract` : seul point
 * d'entrée pour qu'un autre domaine (`Widget`, `Channels`) rattache une
 * conversation à un contact sans connaître `Contacts` au-delà de ce contrat.
 */
final class ContactService implements ContactLinkContract
{
    public function __construct(
        private readonly ContactRepositoryContract $contacts,
        private readonly ContactEventRepositoryContract $events,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Contact>
     */
    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->contacts->paginate($workspaceId, $filters, $perPage);
    }

    public function find(string $workspaceId, string $contactId): Contact
    {
        return $this->contacts->findInWorkspaceOrFail($workspaceId, $contactId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ContactEmailTakenException
     */
    public function create(string $workspaceId, array $attributes): Contact
    {
        $this->guardEmailAvailable($workspaceId, $attributes['email'] ?? null);

        return $this->contacts->create([...$attributes, 'workspace_id' => $workspaceId]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ContactEmailTakenException
     */
    public function update(string $workspaceId, string $contactId, array $attributes): Contact
    {
        $contact = $this->contacts->findInWorkspaceOrFail($workspaceId, $contactId);

        if (array_key_exists('email', $attributes) && $attributes['email'] !== $contact->email) {
            $this->guardEmailAvailable($workspaceId, $attributes['email']);
        }

        return $this->contacts->update($contact, $attributes);
    }

    public function delete(string $workspaceId, string $contactId): void
    {
        $this->contacts->delete($this->contacts->findInWorkspaceOrFail($workspaceId, $contactId));
    }

    /** @return Collection<int, ContactEvent> */
    public function events(string $workspaceId, string $contactId): Collection
    {
        $this->contacts->findInWorkspaceOrFail($workspaceId, $contactId);

        return $this->events->forContact($contactId);
    }

    public function findOrCreateIdByEmail(string $workspaceId, ?string $email, ?string $name = null): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        $contact = $this->contacts->findByEmail($workspaceId, $email);

        if ($contact !== null) {
            return $contact->id;
        }

        return $this->contacts->create([
            'workspace_id' => $workspaceId,
            'email' => $email,
            'name' => $name,
        ])->id;
    }

    public function findOrCreateIdByPhone(string $workspaceId, ?string $phone, ?string $name = null): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $contact = $this->contacts->findByPhone($workspaceId, $phone);

        if ($contact !== null) {
            return $contact->id;
        }

        return $this->contacts->create([
            'workspace_id' => $workspaceId,
            'phone' => $phone,
            'name' => $name,
        ])->id;
    }

    public function findOrCreateIdByExternalId(string $workspaceId, string $channel, ?string $externalId, ?string $name = null): ?string
    {
        if ($externalId === null || $externalId === '') {
            return null;
        }

        $key = "{$channel}_id";
        $contact = $this->contacts->findByCustomAttribute($workspaceId, $key, $externalId);

        if ($contact !== null) {
            return $contact->id;
        }

        return $this->contacts->create([
            'workspace_id' => $workspaceId,
            'name' => $name,
            'custom_attributes' => [$key => $externalId],
        ])->id;
    }

    private function guardEmailAvailable(string $workspaceId, ?string $email): void
    {
        if ($email === null) {
            return;
        }

        if ($this->contacts->findByEmail($workspaceId, $email) !== null) {
            throw ContactEmailTakenException::make();
        }
    }
}
